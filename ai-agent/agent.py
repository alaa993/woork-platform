#!/usr/bin/env python3
import argparse
import threading
import time
from dataclasses import dataclass
from datetime import datetime, timezone
from typing import Optional

import cv2
import numpy as np
import requests
import yaml

from trackers import TrackerError, build_tracker

EVENT_WORK_ACTIVE = "work_active"
EVENT_IDLE = "idle"
EVENT_PHONE = "phone"
EVENT_AWAY = "away"


@dataclass
class EmployeeConfig:
    employee_id: int
    zone: Optional[list[tuple[float, float]]]


@dataclass
class CameraConfig:
    name: str
    rtsp_url: str
    organization_id: int
    room_id: int
    fps: float
    min_motion_area: int
    motion_ratio_threshold: float
    bg_history: int
    bg_var_threshold: float
    bg_detect_shadows: bool
    motion_blur_ksize: int
    motion_erode_iter: int
    motion_dilate_iter: int
    employees: list[EmployeeConfig]


@dataclass
class DetectorConfig:
    enabled: bool
    model_path: Optional[str]
    classes_path: Optional[str]
    person_class_names: list[str]
    phone_class_names: list[str]
    conf_threshold: float
    nms_threshold: float
    input_size: int


@dataclass
class TrackerConfig:
    tracker_type: str
    options: dict


class ApiClient:
    def __init__(self, base_url: str, token: str, timeout: int = 10):
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.session = requests.Session()
        self.session.headers.update({
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
        })

    def ingest(self, organization_id: int, employee_id: int, room_id: int, events: list[dict]) -> None:
        if not events:
            return
        url = f"{self.base_url}/api/agent/ingest"
        payload = {
            "organization_id": organization_id,
            "employee_id": employee_id,
            "room_id": room_id,
            "events": events,
        }
        response = self.session.post(url, json=payload, timeout=self.timeout)
        response.raise_for_status()


class EventBuffer:
    def __init__(self, batch_seconds: int):
        self.batch_seconds = batch_seconds
        self.buffer: list[dict] = []
        self.last_flush = time.time()

    def add(self, event: dict) -> None:
        self.buffer.append(event)

    def should_flush(self) -> bool:
        return (time.time() - self.last_flush) >= self.batch_seconds or len(self.buffer) >= 20

    def flush(self) -> list[dict]:
        if not self.buffer:
            return []
        events = self.buffer[:]
        self.buffer.clear()
        self.last_flush = time.time()
        return events


class YoloDetector:
    def __init__(self, config: DetectorConfig):
        self.config = config
        self.net = None
        self.class_names: list[str] = []
        if not self.config.enabled:
            return
        if not self.config.model_path or not self.config.classes_path:
            return
        self.net = cv2.dnn.readNetFromONNX(self.config.model_path)
        with open(self.config.classes_path, "r", encoding="utf-8") as handle:
            self.class_names = [line.strip() for line in handle if line.strip()]

    def ready(self) -> bool:
        return self.net is not None and len(self.class_names) > 0

    def detect(self, frame: np.ndarray) -> list[tuple[int, int, int, int, str, float]]:
        if not self.ready():
            return []
        size = self.config.input_size
        blob = cv2.dnn.blobFromImage(frame, 1 / 255.0, (size, size), swapRB=True, crop=False)
        self.net.setInput(blob)
        outputs = self.net.forward()
        return self._parse_outputs(outputs, frame.shape[1], frame.shape[0])

    def _parse_outputs(self, outputs: np.ndarray, width: int, height: int) -> list[tuple[int, int, int, int, str, float]]:
        if outputs.ndim == 3:
            outputs = outputs[0]
        boxes = []
        confidences = []
        class_ids = []
        for row in outputs:
            scores = row[5:]
            class_id = int(np.argmax(scores))
            confidence = float(scores[class_id]) * float(row[4])
            if confidence < self.config.conf_threshold:
                continue
            class_name = self.class_names[class_id]
            if class_name not in self.config.person_class_names and class_name not in self.config.phone_class_names:
                continue
            cx, cy, w, h = row[0:4]
            x = int((cx - w / 2) * width)
            y = int((cy - h / 2) * height)
            boxes.append([x, y, int(w * width), int(h * height)])
            confidences.append(confidence)
            class_ids.append(class_id)

        indices = cv2.dnn.NMSBoxes(boxes, confidences, self.config.conf_threshold, self.config.nms_threshold)
        results = []
        for idx in indices:
            i = int(idx)
            x, y, w, h = boxes[i]
            class_name = self.class_names[class_ids[i]]
            results.append((x, y, w, h, class_name, confidences[i]))
        return results


class EmployeeState:
    def __init__(self, employee_id: int, batch_seconds: int, zone: Optional[list[tuple[float, float]]] = None):
        self.employee_id = employee_id
        self.zone = zone
        self.event_buffer = EventBuffer(batch_seconds)
        self.current_event_type: Optional[str] = None
        self.current_event_started_at: Optional[datetime] = None
        self.zone_mask = None
        self.zone_area = None

    def ensure_zone_mask(self, frame_shape: tuple[int, int, int]) -> None:
        if self.zone is None or self.zone_mask is not None:
            return
        height, width = frame_shape[0], frame_shape[1]
        polygon = np.array(
            [(int(x * width), int(y * height)) for x, y in self.zone],
            dtype=np.int32,
        )
        mask = np.zeros((height, width), dtype=np.uint8)
        cv2.fillPoly(mask, [polygon], 255)
        self.zone_mask = mask
        self.zone_area = cv2.countNonZero(mask)

    def update_state(self, new_type: str) -> None:
        now = datetime.now(timezone.utc)
        if self.current_event_type is None:
            self.current_event_type = new_type
            self.current_event_started_at = now
            return

        if new_type == self.current_event_type:
            return

        ended_at = now
        started_at = self.current_event_started_at
        self.event_buffer.add({
            "type": self.current_event_type,
            "started_at": started_at.isoformat(),
            "ended_at": ended_at.isoformat(),
        })

        self.current_event_type = new_type
        self.current_event_started_at = now


class CameraWorker(threading.Thread):
    def __init__(
        self,
        config: CameraConfig,
        api: ApiClient,
        batch_seconds: int,
        detector: YoloDetector,
        tracker_cfg: TrackerConfig,
    ):
        super().__init__(daemon=True)
        self.config = config
        self.api = api
        self.detector = detector
        self.tracker_cfg = tracker_cfg
        self.stop_event = threading.Event()
        self.last_sample_time = 0.0

        self.bg_subtractor = cv2.createBackgroundSubtractorMOG2(
            history=self.config.bg_history,
            varThreshold=self.config.bg_var_threshold,
            detectShadows=self.config.bg_detect_shadows,
        )

        self.employee_states = [
            EmployeeState(emp.employee_id, batch_seconds, emp.zone)
            for emp in self.config.employees
        ]
        self.track_to_employee: dict[int, int] = {}

        self.tracker = self.build_tracker()

    def build_tracker(self):
        try:
            return build_tracker(self.tracker_cfg.tracker_type, self.tracker_cfg.options)
        except TrackerError as exc:
            raise SystemExit(str(exc)) from exc

    def stop(self) -> None:
        self.stop_event.set()

    def run(self) -> None:
        cap = None
        while not self.stop_event.is_set():
            if cap is None or not cap.isOpened():
                cap = cv2.VideoCapture(self.config.rtsp_url)
                time.sleep(0.5)

            ok, frame = cap.read()
            if not ok or frame is None:
                if cap is not None:
                    cap.release()
                cap = None
                time.sleep(1.5)
                continue

            now = time.time()
            if now - self.last_sample_time < (1.0 / max(self.config.fps, 0.2)):
                continue
            self.last_sample_time = now

            for emp in self.employee_states:
                emp.ensure_zone_mask(frame.shape)

            motion_mask = self.detect_motion_mask(frame)
            detections = self.detector.detect(frame) if self.detector.ready() else []
            people_boxes = [
                (x, y, w, h, conf)
                for (x, y, w, h, cls, conf) in detections
                if cls in self.detector.config.person_class_names
            ]
            phone_boxes = [
                (x, y, w, h)
                for (x, y, w, h, cls, _) in detections
                if cls in self.detector.config.phone_class_names
            ]

            if self.uses_zones():
                self.process_with_zones(people_boxes, phone_boxes, motion_mask)
            else:
                self.process_with_tracking(people_boxes, phone_boxes, motion_mask, frame)

            self.flush_if_needed()

        if cap is not None:
            cap.release()

    def uses_zones(self) -> bool:
        return any(emp.zone for emp in self.employee_states)

    def process_with_zones(
        self,
        people_boxes: list[tuple[int, int, int, int, float]],
        phone_boxes: list[tuple[int, int, int, int]],
        motion_mask: np.ndarray,
    ) -> None:
        people_centers = self.boxes_to_centers([box[:4] for box in people_boxes])
        phone_centers = self.boxes_to_centers(phone_boxes)
        for emp in self.employee_states:
            event_type = self.classify_zone_employee(emp, people_centers, phone_centers, motion_mask)
            emp.update_state(event_type)

    def process_with_tracking(
        self,
        people_boxes: list[tuple[int, int, int, int, float]],
        phone_boxes: list[tuple[int, int, int, int]],
        motion_mask: np.ndarray,
        frame: np.ndarray,
    ) -> None:
        tracker_result = self.tracker.update(people_boxes, frame=frame)
        active_tracks = tracker_result.tracks
        self.assign_tracks(active_tracks)

        employee_boxes: dict[int, tuple[int, int, int, int]] = {}
        for track_id, box in active_tracks.items():
            employee_id = self.track_to_employee.get(track_id)
            if employee_id is not None:
                employee_boxes[employee_id] = box

        for emp in self.employee_states:
            box = employee_boxes.get(emp.employee_id)
            event_type = self.classify_tracked_employee(emp, box, phone_boxes, motion_mask)
            emp.update_state(event_type)

        self.release_missing_tracks(active_tracks)

    def assign_tracks(self, active_tracks: dict[int, tuple[int, int, int, int]]) -> None:
        assigned_employees = set(self.track_to_employee.values())
        available_employees = [
            emp.employee_id for emp in self.employee_states if emp.employee_id not in assigned_employees
        ]
        for track_id in active_tracks.keys():
            if track_id in self.track_to_employee:
                continue
            if not available_employees:
                break
            self.track_to_employee[track_id] = available_employees.pop(0)

    def release_missing_tracks(self, active_tracks: dict[int, tuple[int, int, int, int]]) -> None:
        active_ids = set(active_tracks.keys())
        to_release = [tid for tid in self.track_to_employee if tid not in active_ids]
        for tid in to_release:
            del self.track_to_employee[tid]

    def classify_zone_employee(
        self,
        emp: EmployeeState,
        people_centers: list[tuple[int, int]],
        phone_centers: list[tuple[int, int]],
        motion_mask: np.ndarray,
    ) -> str:
        if emp.zone_mask is None or emp.zone_area is None:
            return EVENT_AWAY

        person_present = self.is_any_center_in_zone(people_centers, emp.zone_mask)
        if not person_present:
            return EVENT_AWAY

        if phone_centers and self.is_any_center_in_zone(phone_centers, emp.zone_mask):
            return EVENT_PHONE

        motion_pixels = cv2.countNonZero(cv2.bitwise_and(motion_mask, motion_mask, mask=emp.zone_mask))
        if motion_pixels < self.config.min_motion_area:
            return EVENT_IDLE

        ratio = motion_pixels / max(emp.zone_area, 1)
        if ratio >= self.config.motion_ratio_threshold:
            return EVENT_WORK_ACTIVE

        return EVENT_IDLE

    def classify_tracked_employee(
        self,
        emp: EmployeeState,
        bbox: Optional[tuple[int, int, int, int]],
        phone_boxes: list[tuple[int, int, int, int]],
        motion_mask: np.ndarray,
    ) -> str:
        if bbox is None:
            return EVENT_AWAY
        x, y, w, h = bbox
        roi_mask = np.zeros(motion_mask.shape[:2], dtype=np.uint8)
        cv2.rectangle(roi_mask, (x, y), (x + w, y + h), 255, -1)
        motion_pixels = cv2.countNonZero(cv2.bitwise_and(motion_mask, motion_mask, mask=roi_mask))

        if self.is_any_box_inside(phone_boxes, bbox):
            return EVENT_PHONE

        if motion_pixels < self.config.min_motion_area:
            return EVENT_IDLE

        ratio = motion_pixels / max(w * h, 1)
        if ratio >= self.config.motion_ratio_threshold:
            return EVENT_WORK_ACTIVE

        return EVENT_IDLE

    def detect_motion_mask(self, frame: np.ndarray) -> np.ndarray:
        mask = self.bg_subtractor.apply(frame)
        if self.config.motion_blur_ksize > 0:
            ksize = self.config.motion_blur_ksize
            if ksize % 2 == 0:
                ksize += 1
            mask = cv2.GaussianBlur(mask, (ksize, ksize), 0)
        _, thresh = cv2.threshold(mask, 200, 255, cv2.THRESH_BINARY)
        if self.config.motion_erode_iter > 0:
            thresh = cv2.erode(thresh, None, iterations=self.config.motion_erode_iter)
        if self.config.motion_dilate_iter > 0:
            thresh = cv2.dilate(thresh, None, iterations=self.config.motion_dilate_iter)
        return thresh

    @staticmethod
    def boxes_to_centers(boxes: list[tuple[int, int, int, int]]) -> list[tuple[int, int]]:
        centers = []
        for x, y, w, h in boxes:
            centers.append((int(x + w / 2), int(y + h / 2)))
        return centers

    @staticmethod
    def is_any_center_in_zone(centers: list[tuple[int, int]], mask: np.ndarray) -> bool:
        for x, y in centers:
            if 0 <= y < mask.shape[0] and 0 <= x < mask.shape[1] and mask[y, x] > 0:
                return True
        return False

    @staticmethod
    def is_any_box_inside(boxes: list[tuple[int, int, int, int]], target: tuple[int, int, int, int]) -> bool:
        tx, ty, tw, th = target
        for x, y, w, h in boxes:
            cx = x + w / 2
            cy = y + h / 2
            if tx <= cx <= tx + tw and ty <= cy <= ty + th:
                return True
        return False

    def flush_if_needed(self) -> None:
        for emp in self.employee_states:
            if not emp.event_buffer.should_flush():
                continue
            events = emp.event_buffer.flush()
            try:
                self.api.ingest(
                    organization_id=self.config.organization_id,
                    employee_id=emp.employee_id,
                    room_id=self.config.room_id,
                    events=events,
                )
            except Exception:
                for event in events:
                    emp.event_buffer.add(event)


def load_config(path: str) -> tuple[ApiClient, int, list[CameraConfig], DetectorConfig, TrackerConfig]:
    with open(path, "r", encoding="utf-8") as handle:
        cfg = yaml.safe_load(handle)

    api = ApiClient(cfg["api_base_url"], cfg["results_token"])
    batch_seconds = int(cfg.get("batch_seconds", 30))

    detector_cfg = cfg.get("detector", {}) or {}
    detector = DetectorConfig(
        enabled=bool(detector_cfg.get("enabled", False)),
        model_path=detector_cfg.get("model_path"),
        classes_path=detector_cfg.get("classes_path"),
        person_class_names=detector_cfg.get("person_class_names", ["person"]),
        phone_class_names=detector_cfg.get("phone_class_names", ["cell phone", "mobile phone"]),
        conf_threshold=float(detector_cfg.get("conf_threshold", 0.4)),
        nms_threshold=float(detector_cfg.get("nms_threshold", 0.45)),
        input_size=int(detector_cfg.get("input_size", 640)),
    )

    tracker_cfg = cfg.get("tracker", {}) or {}
    tracker = TrackerConfig(
        tracker_type=str(tracker_cfg.get("type", "bytetrack")),
        options=dict(tracker_cfg),
    )

    cameras_cfg = []
    for cam in cfg.get("cameras", []):
        motion = cam.get("motion", {})
        employees = []
        if "employees" in cam and cam["employees"]:
            for emp in cam["employees"]:
                zone_value = emp.get("zone")
                employees.append(EmployeeConfig(
                    employee_id=int(emp["employee_id"]),
                    zone=[(float(x), float(y)) for x, y in zone_value] if zone_value else None,
                ))
        else:
            employees.append(EmployeeConfig(
                employee_id=int(cam["employee_id"]),
                zone=None,
            ))

        cameras_cfg.append(CameraConfig(
            name=cam["name"],
            rtsp_url=cam["rtsp_url"],
            organization_id=int(cam["organization_id"]),
            room_id=int(cam["room_id"]),
            fps=float(cam.get("fps", 2)),
            min_motion_area=int(motion.get("min_motion_area", 6000)),
            motion_ratio_threshold=float(motion.get("motion_ratio_threshold", 0.02)),
            bg_history=int(motion.get("bg_history", 300)),
            bg_var_threshold=float(motion.get("bg_var_threshold", 16)),
            bg_detect_shadows=bool(motion.get("bg_detect_shadows", False)),
            motion_blur_ksize=int(motion.get("blur_ksize", 0)),
            motion_erode_iter=int(motion.get("erode_iter", 0)),
            motion_dilate_iter=int(motion.get("dilate_iter", 0)),
            employees=employees,
        ))

    return api, batch_seconds, cameras_cfg, detector, tracker


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--config", required=True, help="Path to config.yml")
    args = parser.parse_args()

    api, batch_seconds, cameras_cfg, detector_cfg, tracker_cfg = load_config(args.config)
    if not cameras_cfg:
        raise SystemExit("No cameras configured")

    detector = YoloDetector(detector_cfg)
    workers = [CameraWorker(cfg, api, batch_seconds, detector, tracker_cfg) for cfg in cameras_cfg]
    for worker in workers:
        worker.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        for worker in workers:
            worker.stop()


if __name__ == "__main__":
    main()
