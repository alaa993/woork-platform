from __future__ import annotations

import math
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Optional

try:
    import cv2  # type: ignore
except Exception:  # noqa: BLE001
    cv2 = None


@dataclass
class BoundingBox:
    x: int
    y: int
    w: int
    h: int
    label: str = "person"
    confidence: Optional[float] = None

    @property
    def centroid(self) -> tuple[int, int]:
        return (self.x + self.w // 2, self.y + self.h // 2)

    @property
    def area(self) -> int:
        return max(0, self.w) * max(0, self.h)

    def anchor_point(self, mode: str = "center") -> tuple[int, int]:
        mode = mode.strip().lower()
        if mode in {"foot", "footpoint", "bottom_center"}:
            return (self.x + self.w // 2, self.y + self.h)
        if mode in {"top_center", "head"}:
            return (self.x + self.w // 2, self.y)
        return self.centroid


@dataclass
class Track:
    track_id: int
    bbox: BoundingBox
    hits: int
    missing_frames: int


def point_in_polygon(point: tuple[int, int], polygon: Any) -> bool:
    x, y = point
    inside = False
    points = [(int(px), int(py)) for px, py in polygon]
    if len(points) < 3:
        return True

    j = len(points) - 1
    for i, (xi, yi) in enumerate(points):
        xj, yj = points[j]
        intersects = ((yi > y) != (yj > y)) and (
            x < ((xj - xi) * (y - yi) / ((yj - yi) or 1)) + xi
        )
        if intersects:
            inside = not inside
        j = i
    return inside


def bbox_iou(a: BoundingBox, b: BoundingBox) -> float:
    x1 = max(a.x, b.x)
    y1 = max(a.y, b.y)
    x2 = min(a.x + a.w, b.x + b.w)
    y2 = min(a.y + a.h, b.y + b.h)

    intersection = max(0, x2 - x1) * max(0, y2 - y1)
    if intersection <= 0:
        return 0.0

    union = a.area + b.area - intersection
    return intersection / union if union > 0 else 0.0


class DetectionBackend:
    def available(self) -> bool:
        return False

    def supports_label(self, label: str) -> bool:
        return False

    def detect(self, frame: Any, labels: Optional[list[str]] = None) -> list[BoundingBox]:
        return []


class HOGPersonDetector(DetectionBackend):
    def __init__(self, config: Optional[dict[str, Any]] = None) -> None:
        self.config = config or {}
        self._hog = None
        if cv2 is not None:
            self._hog = cv2.HOGDescriptor()
            self._hog.setSVMDetector(cv2.HOGDescriptor_getDefaultPeopleDetector())

    def available(self) -> bool:
        return self._hog is not None

    def supports_label(self, label: str) -> bool:
        return label in {"person", "*"}

    def detect(self, frame: Any, labels: Optional[list[str]] = None) -> list[BoundingBox]:
        if self._hog is None or frame is None:
            return []
        if labels and "person" not in labels and "*" not in labels:
            return []

        win_stride = tuple(self.config.get("hog_win_stride", [8, 8]))
        padding = tuple(self.config.get("hog_padding", [8, 8]))
        scale = float(self.config.get("hog_scale", 1.05))
        min_width = int(self.config.get("min_person_width", 32))
        min_height = int(self.config.get("min_person_height", 64))

        boxes, weights = self._hog.detectMultiScale(
            frame,
            winStride=win_stride,
            padding=padding,
            scale=scale,
        )

        results: list[BoundingBox] = []
        for (x, y, w, h), weight in zip(boxes, weights):
            if w < min_width or h < min_height:
                continue
            results.append(
                BoundingBox(
                    x=int(x),
                    y=int(y),
                    w=int(w),
                    h=int(h),
                    label="person",
                    confidence=float(weight),
                )
            )
        return self._suppress_nested(results)

    def _suppress_nested(self, boxes: list[BoundingBox]) -> list[BoundingBox]:
        filtered: list[BoundingBox] = []
        for candidate in sorted(boxes, key=lambda item: item.area, reverse=True):
            if any(self._contains(existing, candidate) for existing in filtered):
                continue
            filtered.append(candidate)
        return filtered

    @staticmethod
    def _contains(a: BoundingBox, b: BoundingBox) -> bool:
        return (
            b.x >= a.x
            and b.y >= a.y
            and (b.x + b.w) <= (a.x + a.w)
            and (b.y + b.h) <= (a.y + a.h)
        )


class OpenCVDnnObjectDetector(DetectionBackend):
    def __init__(self, config: Optional[dict[str, Any]] = None) -> None:
        self.config = config or {}
        self._model = None
        self._labels = self._load_labels()

        model_path = self.config.get("dnn_model_path")
        config_path = self.config.get("dnn_config_path")
        if cv2 is None or not model_path:
            return

        model_file = Path(str(model_path))
        config_file = Path(str(config_path)) if config_path else None
        if not model_file.exists() or (config_file and not config_file.exists()):
            return

        try:
            self._model = cv2.dnn_DetectionModel(str(model_file), str(config_file) if config_file else "")
            self._model.setInputSize(
                int(self.config.get("dnn_input_width", 320)),
                int(self.config.get("dnn_input_height", 320)),
            )
            self._model.setInputScale(float(self.config.get("dnn_scale", 1.0 / 127.5)))
            self._model.setInputMean(tuple(self.config.get("dnn_mean", [127.5, 127.5, 127.5])))
            self._model.setInputSwapRB(bool(self.config.get("dnn_swap_rb", True)))
        except Exception:  # noqa: BLE001
            self._model = None

    def available(self) -> bool:
        return self._model is not None

    def supports_label(self, label: str) -> bool:
        normalized = label.strip().lower()
        return normalized in self._labels.values() or normalized == "*"

    def detect(self, frame: Any, labels: Optional[list[str]] = None) -> list[BoundingBox]:
        if self._model is None or frame is None:
            return []

        confidence_threshold = float(self.config.get("dnn_confidence_threshold", 0.45))
        nms_threshold = float(self.config.get("dnn_nms_threshold", 0.4))
        wanted = {label.strip().lower() for label in (labels or ["*"])}

        class_ids, confidences, boxes = self._model.detect(
            frame,
            confThreshold=confidence_threshold,
            nmsThreshold=nms_threshold,
        )

        detections: list[BoundingBox] = []
        for class_id, confidence, box in zip(class_ids.flatten(), confidences.flatten(), boxes):
            label = self._labels.get(int(class_id), str(int(class_id))).strip().lower()
            if "*" not in wanted and label not in wanted:
                continue
            x, y, w, h = box
            detections.append(
                BoundingBox(
                    x=int(x),
                    y=int(y),
                    w=int(w),
                    h=int(h),
                    label=label,
                    confidence=float(confidence),
                )
            )
        return detections

    def _load_labels(self) -> dict[int, str]:
        configured_labels = self.config.get("dnn_labels")
        if isinstance(configured_labels, list):
            return {index + 1: str(label) for index, label in enumerate(configured_labels)}

        labels_path = self.config.get("dnn_labels_path")
        if labels_path:
            file_path = Path(str(labels_path))
            if file_path.exists():
                return {
                    index + 1: line.strip()
                    for index, line in enumerate(file_path.read_text().splitlines())
                    if line.strip()
                }

        return {
            1: "person",
            77: "cell phone",
        }


class CentroidTracker:
    def __init__(self, max_distance: float = 80.0, max_missing_frames: int = 5) -> None:
        self.max_distance = max_distance
        self.max_missing_frames = max_missing_frames
        self._next_track_id = 1
        self._tracks: dict[int, Track] = {}

    def update(self, detections: list[BoundingBox]) -> list[Track]:
        if not detections:
            self._increment_missing()
            self._prune()
            return list(self._tracks.values())

        unmatched_tracks = set(self._tracks.keys())
        matched_detection_indexes: set[int] = set()

        for detection_index, detection in enumerate(detections):
            track_id = self._match_track(detection, unmatched_tracks)
            if track_id is None:
                self._register(detection)
                continue

            track = self._tracks[track_id]
            track.bbox = detection
            track.hits += 1
            track.missing_frames = 0
            unmatched_tracks.discard(track_id)
            matched_detection_indexes.add(detection_index)

        for detection_index, detection in enumerate(detections):
            if detection_index not in matched_detection_indexes and not self._is_detection_assigned(detection):
                self._register(detection)

        for track_id in unmatched_tracks:
            self._tracks[track_id].missing_frames += 1

        self._prune()
        return list(self._tracks.values())

    def _match_track(self, detection: BoundingBox, candidate_ids: set[int]) -> Optional[int]:
        best_track_id: Optional[int] = None
        best_distance = self.max_distance
        cx, cy = detection.centroid

        for track_id in candidate_ids:
            track_cx, track_cy = self._tracks[track_id].bbox.centroid
            distance = math.dist((cx, cy), (track_cx, track_cy))
            if distance <= best_distance:
                best_distance = distance
                best_track_id = track_id

        return best_track_id

    def _is_detection_assigned(self, detection: BoundingBox) -> bool:
        return any(track.bbox.centroid == detection.centroid for track in self._tracks.values())

    def _register(self, detection: BoundingBox) -> None:
        track_id = self._next_track_id
        self._next_track_id += 1
        self._tracks[track_id] = Track(
            track_id=track_id,
            bbox=detection,
            hits=1,
            missing_frames=0,
        )

    def _increment_missing(self) -> None:
        for track in self._tracks.values():
            track.missing_frames += 1

    def _prune(self) -> None:
        self._tracks = {
            track_id: track
            for track_id, track in self._tracks.items()
            if track.missing_frames <= self.max_missing_frames
        }


def build_detection_backend(config: Optional[dict[str, Any]] = None) -> DetectionBackend:
    detector_name = str((config or {}).get("detector", "auto")).strip().lower()
    has_dnn_artifacts = any((config or {}).get(key) for key in ("dnn_model_path", "dnn_config_path", "dnn_labels_path"))

    if detector_name in {"auto", "opencv_dnn"} and has_dnn_artifacts:
        detector = OpenCVDnnObjectDetector(config)
        if detector.available():
            return detector

    return HOGPersonDetector(config)
