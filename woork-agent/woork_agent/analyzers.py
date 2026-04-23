from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime, timezone
from typing import Any, Optional

from .models import CameraConfig, EventPayload
from .vision import BoundingBox, CentroidTracker, bbox_iou, build_detection_backend, point_in_polygon

try:
    import cv2  # type: ignore
except Exception:  # noqa: BLE001
    cv2 = None


@dataclass
class AnalyzerResult:
    events: list[EventPayload]
    observations: dict[str, Any]


class BaseAnalyzer:
    def __init__(self, camera: CameraConfig) -> None:
        self.camera = camera
        self.config = camera.analysis_config or {}
        self._last_emitted_state: Optional[str] = None
        self._last_emitted_at = 0.0
        self._last_seen_at = 0.0

    @property
    def employee_id(self) -> Optional[int]:
        value = self.config.get("assigned_employee_id")
        return int(value) if value else None

    def analyze(self, frame: Any, now_ts: float) -> AnalyzerResult:
        return AnalyzerResult(events=[], observations={})

    def _emit_state_event(
        self,
        state: str,
        event_type: str,
        now_ts: float,
        meta: Optional[dict[str, Any]] = None,
    ) -> Optional[EventPayload]:
        employee_id = self.employee_id
        if not employee_id or not self.camera.room_id:
            return None

        min_gap = int(self.config.get("min_event_gap_seconds", 60))
        if self._last_emitted_state == state and (now_ts - self._last_emitted_at) < min_gap:
            return None

        ended_at = datetime.fromtimestamp(now_ts, timezone.utc)
        started_at = datetime.fromtimestamp(max(0, now_ts - min_gap), timezone.utc)

        self._last_emitted_state = state
        self._last_emitted_at = now_ts

        return EventPayload(
            camera_id=self.camera.id,
            employee_id=employee_id,
            room_id=int(self.camera.room_id),
            type=event_type,
            started_at=started_at.isoformat(),
            ended_at=ended_at.isoformat(),
            meta=meta or {},
        )


class IntervalAnalyzer(BaseAnalyzer):
    def analyze(self, frame: Any, now_ts: float) -> AnalyzerResult:
        event_type = self.config.get("default_event_type", self._default_event_type())
        event = self._emit_state_event(
            state="interval",
            event_type=event_type,
            now_ts=now_ts,
            meta={
                "source": "camera_worker",
                "generator": "interval_analyzer",
                "analysis_mode": self.camera.analysis_mode,
            },
        )
        return AnalyzerResult(
            events=[event] if event else [],
            observations={"analyzer": "interval", "frame_available": frame is not None},
        )

    def _default_event_type(self) -> str:
        return {
            "desk_monitoring": "work_active",
            "entrance_monitoring": "work_active",
            "cashier_monitoring": "work_active",
            "warehouse_monitoring": "away",
        }.get(self.camera.analysis_mode, "work_active")


class MotionPresenceAnalyzer(BaseAnalyzer):
    def __init__(self, camera: CameraConfig) -> None:
        super().__init__(camera)
        self._previous_gray: Any = None
        self._last_frame_at = 0.0
        self._last_motion_at = 0.0

    def analyze(self, frame: Any, now_ts: float) -> AnalyzerResult:
        if cv2 is None:
            return AnalyzerResult(
                events=[],
                observations={"analyzer": "motion_presence", "fallback_required": True},
            )

        away_after = int(self.config.get("away_after_seconds", 180))
        idle_after = int(self.config.get("idle_after_seconds", 300))
        motion_threshold = float(self.config.get("motion_threshold", 12.0))
        min_motion_ratio = float(self.config.get("min_motion_ratio", 0.01))

        if frame is None:
            if self._last_frame_at and now_ts - self._last_frame_at >= away_after:
                event = self._emit_state_event(
                    state="away",
                    event_type=self.config.get("away_event_type", "away"),
                    now_ts=now_ts,
                    meta={
                        "source": "camera_worker",
                        "generator": "motion_presence_analyzer",
                        "analysis_mode": self.camera.analysis_mode,
                        "reason": "no_frame_timeout",
                    },
                )
                return AnalyzerResult(
                    events=[event] if event else [],
                    observations={
                        "analyzer": "motion_presence",
                        "presence_state": "away",
                        "motion_score": 0.0,
                    },
                )

            return AnalyzerResult(
                events=[],
                observations={"analyzer": "motion_presence", "presence_state": "no_frame"},
            )

        self._last_frame_at = now_ts
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        gray = cv2.GaussianBlur(gray, (21, 21), 0)

        motion_score = 0.0
        motion_ratio = 0.0
        if self._previous_gray is not None:
            delta = cv2.absdiff(self._previous_gray, gray)
            motion_score = float(delta.mean())
            _, thresh = cv2.threshold(delta, 25, 255, cv2.THRESH_BINARY)
            motion_pixels = int(cv2.countNonZero(thresh))
            total_pixels = int(thresh.shape[0] * thresh.shape[1]) or 1
            motion_ratio = motion_pixels / total_pixels

        self._previous_gray = gray

        state = "present"
        if motion_score >= motion_threshold and motion_ratio >= min_motion_ratio:
            self._last_motion_at = now_ts
            event = self._emit_state_event(
                state="active",
                event_type=self.config.get("presence_event_type", "work_active"),
                now_ts=now_ts,
                meta={
                    "source": "camera_worker",
                    "generator": "motion_presence_analyzer",
                    "analysis_mode": self.camera.analysis_mode,
                    "motion_score": motion_score,
                    "motion_ratio": motion_ratio,
                },
            )
            return AnalyzerResult(
                events=[event] if event else [],
                observations={
                    "analyzer": "motion_presence",
                    "presence_state": "active",
                    "motion_score": round(motion_score, 4),
                    "motion_ratio": round(motion_ratio, 4),
                },
            )

        if self._last_motion_at and now_ts - self._last_motion_at >= idle_after:
            state = "idle"
            event = self._emit_state_event(
                state="idle",
                event_type=self.config.get("idle_event_type", "idle"),
                now_ts=now_ts,
                meta={
                    "source": "camera_worker",
                    "generator": "motion_presence_analyzer",
                    "analysis_mode": self.camera.analysis_mode,
                    "motion_score": motion_score,
                    "motion_ratio": motion_ratio,
                },
            )
            return AnalyzerResult(
                events=[event] if event else [],
                observations={
                    "analyzer": "motion_presence",
                    "presence_state": state,
                    "motion_score": round(motion_score, 4),
                    "motion_ratio": round(motion_ratio, 4),
                },
            )

        return AnalyzerResult(
            events=[],
            observations={
                "analyzer": "motion_presence",
                "presence_state": state,
                "motion_score": round(motion_score, 4),
                "motion_ratio": round(motion_ratio, 4),
            },
        )


class VisionPeopleAnalyzer(BaseAnalyzer):
    def __init__(self, camera: CameraConfig) -> None:
        super().__init__(camera)
        self._detector = build_detection_backend(self.config)
        self._tracker = CentroidTracker(
            max_distance=float(self.config.get("tracking_max_distance", 90)),
            max_missing_frames=int(self.config.get("tracking_max_missing_frames", 6)),
        )
        self._previous_gray: Any = None
        self._last_detected_person_at = 0.0
        self._last_motion_at = 0.0
        self._phone_positive_frames = 0
        self._warmup_started_at = 0.0

    def analyze(self, frame: Any, now_ts: float) -> AnalyzerResult:
        if cv2 is None or frame is None or not self._detector.available():
            return AnalyzerResult(
                events=[],
                observations={
                    "analyzer": "vision_people",
                    "fallback_required": True,
                    "detector": self._detector.__class__.__name__,
                },
            )

        away_after = int(self.config.get("away_after_seconds", 180))
        idle_after = int(self.config.get("idle_after_seconds", 300))
        motion_threshold = float(self.config.get("motion_threshold", 12.0))
        min_motion_ratio = float(self.config.get("min_motion_ratio", 0.01))
        warmup_seconds = int(self.config.get("warmup_seconds", 15))
        phone_min_frames = int(self.config.get("phone_min_frames", 2))
        detections = self._filter_roi(self._detector.detect(frame, labels=["person"]))
        tracks = self._tracker.update(detections)
        active_tracks = [track for track in tracks if track.missing_frames == 0]
        phone_supported = self._detector.supports_label("cell phone") or self._detector.supports_label("phone")
        phone_detections = self._filter_roi(
            self._detector.detect(frame, labels=["cell phone", "phone"]) if phone_supported else []
        )
        matched_phone_detections = self._match_phone_detections(phone_detections, active_tracks)

        motion_score, motion_ratio = self._motion_metrics(frame)
        if not self._warmup_started_at:
            self._warmup_started_at = now_ts

        if active_tracks:
            self._last_detected_person_at = now_ts
            self._last_seen_at = now_ts
            if not self._last_motion_at:
                self._last_motion_at = now_ts
            if matched_phone_detections:
                self._phone_positive_frames += 1
            else:
                self._phone_positive_frames = 0

            if self._phone_positive_frames >= phone_min_frames:
                event = self._emit_state_event(
                    state="phone",
                    event_type=self.config.get("phone_event_type", "phone"),
                    now_ts=now_ts,
                    meta={
                        "source": "camera_worker",
                        "generator": "vision_people_analyzer",
                        "analysis_mode": self.camera.analysis_mode,
                        "track_ids": [track.track_id for track in active_tracks],
                        "person_count": len(active_tracks),
                        "phone_count": len(matched_phone_detections),
                    },
                )
                return AnalyzerResult(
                    events=[event] if event else [],
                    observations={
                        "analyzer": "vision_people",
                        "detector": self._detector.__class__.__name__,
                        "presence_state": "phone",
                        "person_count": len(active_tracks),
                        "phone_count": len(matched_phone_detections),
                        "active_track_ids": [track.track_id for track in active_tracks],
                        "motion_score": round(motion_score, 4),
                        "motion_ratio": round(motion_ratio, 4),
                        "phone_supported": phone_supported,
                    },
                )

            if motion_score >= motion_threshold and motion_ratio >= min_motion_ratio:
                self._last_motion_at = now_ts

            if now_ts - self._last_motion_at >= idle_after:
                event = self._emit_state_event(
                    state="idle",
                    event_type=self.config.get("idle_event_type", "idle"),
                    now_ts=now_ts,
                    meta={
                        "source": "camera_worker",
                        "generator": "vision_people_analyzer",
                        "analysis_mode": self.camera.analysis_mode,
                        "motion_score": motion_score,
                        "motion_ratio": motion_ratio,
                        "track_ids": [track.track_id for track in active_tracks],
                        "person_count": len(active_tracks),
                    },
                )
                return AnalyzerResult(
                    events=[event] if event else [],
                    observations={
                        "analyzer": "vision_people",
                        "detector": self._detector.__class__.__name__,
                        "presence_state": "idle",
                        "person_count": len(active_tracks),
                        "phone_count": len(matched_phone_detections),
                        "active_track_ids": [track.track_id for track in active_tracks],
                        "motion_score": round(motion_score, 4),
                        "motion_ratio": round(motion_ratio, 4),
                        "phone_supported": phone_supported,
                    },
                )

            if now_ts - self._warmup_started_at >= warmup_seconds:
                event = self._emit_state_event(
                    state="active",
                    event_type=self.config.get("presence_event_type", "work_active"),
                    now_ts=now_ts,
                    meta={
                        "source": "camera_worker",
                        "generator": "vision_people_analyzer",
                        "analysis_mode": self.camera.analysis_mode,
                        "motion_score": motion_score,
                        "motion_ratio": motion_ratio,
                        "track_ids": [track.track_id for track in active_tracks],
                        "person_count": len(active_tracks),
                    },
                )
                return AnalyzerResult(
                    events=[event] if event else [],
                    observations={
                        "analyzer": "vision_people",
                        "detector": self._detector.__class__.__name__,
                        "presence_state": "active",
                        "person_count": len(active_tracks),
                        "phone_count": len(matched_phone_detections),
                        "active_track_ids": [track.track_id for track in active_tracks],
                        "motion_score": round(motion_score, 4),
                        "motion_ratio": round(motion_ratio, 4),
                        "phone_supported": phone_supported,
                    },
                )

            return AnalyzerResult(
                events=[],
                observations={
                    "analyzer": "vision_people",
                    "detector": self._detector.__class__.__name__,
                    "presence_state": "present",
                    "person_count": len(active_tracks),
                    "phone_count": len(matched_phone_detections),
                    "active_track_ids": [track.track_id for track in active_tracks],
                    "motion_score": round(motion_score, 4),
                    "motion_ratio": round(motion_ratio, 4),
                    "phone_supported": phone_supported,
                },
            )

        self._phone_positive_frames = 0
        if self._last_detected_person_at and (now_ts - self._last_detected_person_at) >= away_after:
            event = self._emit_state_event(
                state="away",
                event_type=self.config.get("away_event_type", "away"),
                now_ts=now_ts,
                meta={
                    "source": "camera_worker",
                    "generator": "vision_people_analyzer",
                    "analysis_mode": self.camera.analysis_mode,
                    "reason": "no_person_detected",
                },
            )
            return AnalyzerResult(
                events=[event] if event else [],
                observations={
                    "analyzer": "vision_people",
                    "detector": self._detector.__class__.__name__,
                    "presence_state": "away",
                    "person_count": 0,
                    "phone_count": 0,
                    "active_track_ids": [],
                    "motion_score": round(motion_score, 4),
                    "motion_ratio": round(motion_ratio, 4),
                    "phone_supported": phone_supported,
                },
            )

        return AnalyzerResult(
            events=[],
            observations={
                "analyzer": "vision_people",
                "detector": self._detector.__class__.__name__,
                "presence_state": "scanning",
                "person_count": 0,
                "phone_count": 0,
                "active_track_ids": [],
                "motion_score": round(motion_score, 4),
                "motion_ratio": round(motion_ratio, 4),
                "phone_supported": phone_supported,
            },
        )

    def _filter_roi(self, detections: list[Any]) -> list[Any]:
        roi = self.camera.roi or {}
        polygon = roi.get("work_zone") or roi.get("zone") or roi.get("polygon")
        if not polygon:
            return detections

        anchor = str(self.config.get("roi_anchor", "footpoint"))
        return [
            detection
            for detection in detections
            if point_in_polygon(detection.anchor_point(anchor), polygon)
        ]

    def _match_phone_detections(self, phones: list[BoundingBox], tracks: list[Any]) -> list[BoundingBox]:
        if not phones or not tracks:
            return []

        matches: list[BoundingBox] = []
        min_iou = float(self.config.get("phone_overlap_iou", 0.01))
        for phone in phones:
            for track in tracks:
                if bbox_iou(phone, track.bbox) >= min_iou:
                    matches.append(phone)
                    break
        return matches

    def _motion_metrics(self, frame: Any) -> tuple[float, float]:
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        gray = cv2.GaussianBlur(gray, (21, 21), 0)

        motion_score = 0.0
        motion_ratio = 0.0
        if self._previous_gray is not None:
            delta = cv2.absdiff(self._previous_gray, gray)
            motion_score = float(delta.mean())
            _, thresh = cv2.threshold(delta, 25, 255, cv2.THRESH_BINARY)
            motion_pixels = int(cv2.countNonZero(thresh))
            total_pixels = int(thresh.shape[0] * thresh.shape[1]) or 1
            motion_ratio = motion_pixels / total_pixels

        self._previous_gray = gray
        return motion_score, motion_ratio


def build_analyzer(camera: CameraConfig) -> BaseAnalyzer:
    config = camera.analysis_config or {}
    analyzer_name = str(config.get("analyzer", "interval")).strip().lower()

    if analyzer_name == "vision_people":
        if cv2 is not None:
            return VisionPeopleAnalyzer(camera)
        fallback = str(config.get("fallback_analyzer", "motion_presence")).strip().lower()
        if fallback == "motion_presence":
            return MotionPresenceAnalyzer(camera)
        if fallback == "interval":
            return IntervalAnalyzer(camera)

    if analyzer_name == "motion_presence":
        if cv2 is not None:
            return MotionPresenceAnalyzer(camera)
        fallback = str(config.get("fallback_analyzer", "interval")).strip().lower()
        if fallback == "interval":
            return IntervalAnalyzer(camera)

    return IntervalAnalyzer(camera)
