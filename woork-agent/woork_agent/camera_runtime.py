from __future__ import annotations

import logging
import threading
import time
from dataclasses import asdict
from datetime import datetime, timezone
from typing import Any

from .analyzers import build_analyzer
from .models import AgentSettings, CameraConfig, CameraRuntimeStatus, EventPayload

try:
    import cv2  # type: ignore
except Exception:  # noqa: BLE001
    cv2 = None

logger = logging.getLogger(__name__)


class CameraWorker:
    def __init__(self, camera: CameraConfig, settings: AgentSettings) -> None:
        self.camera = camera
        self.settings = settings
        self._analyzer = build_analyzer(camera)
        self._lock = threading.Lock()
        self._status = CameraRuntimeStatus(
            id=camera.id,
            stream_status="pending",
            health_message="Worker not started yet",
            observations={"reconnect_attempts": 0},
        )
        self._thread: threading.Thread | None = None
        self._running = False
        self._pending_events: list[EventPayload] = []
        self._capture: Any = None
        self._reconnect_attempts = 0
        self._consecutive_failures = 0
        self._last_frame_ts = 0.0
        self._last_success_ts = 0.0
        self._last_process_ts = 0.0
        self._frames_processed = 0
        self._dropped_frames = 0
        self._processing_ms = 0.0

    def start(self) -> None:
        if self._thread and self._thread.is_alive():
            return

        self._running = True
        self._thread = threading.Thread(target=self._run_loop, daemon=True, name=f"camera-{self.camera.id}")
        self._thread.start()

    def stop(self) -> None:
        self._running = False
        if self._thread and self._thread.is_alive():
            self._thread.join(timeout=3)
        self._release_capture()

    def reconfigure(self, camera: CameraConfig) -> None:
        with self._lock:
            rtsp_changed = self.camera.rtsp_url != camera.rtsp_url
            self.camera = camera
            self._analyzer = build_analyzer(camera)
        if rtsp_changed:
            self._release_capture()

    def snapshot_status(self) -> dict[str, Any]:
        with self._lock:
            return asdict(self._status)

    def drain_events(self) -> list[EventPayload]:
        with self._lock:
            events = list(self._pending_events)
            self._pending_events.clear()
            return events

    def _run_loop(self) -> None:
        while self._running:
            started = time.time()
            try:
                status = self._probe_stream()
            except Exception as exc:  # noqa: BLE001
                logger.exception("Camera worker %s crashed during probe: %s", self.camera.id, exc)
                status = self._status_from_error("warning", f"Runtime error: {exc}")
                self._consecutive_failures += 1
                self._release_capture()

            with self._lock:
                self._status = status

            delay = max(self.settings.runtime_loop_interval_seconds, self._sampling_interval_seconds())
            elapsed = time.time() - started
            time.sleep(max(0.1, delay - elapsed))

    def _probe_stream(self) -> CameraRuntimeStatus:
        if hasattr(self.camera, "is_enabled") and not self.camera.is_enabled:
            return self._status_from_error("pending", "Camera disabled in config")

        if not self.camera.rtsp_url:
            self._run_analyzer(frame=None)
            return self._status_from_error("misconfigured", "Missing RTSP URL")

        if cv2 is None:
            status = self._status_from_error("online", "OpenCV not installed; runtime probe skipped")
            status.last_frame_at = datetime.now(timezone.utc).isoformat()
            status.observations = {
                **(status.observations or {}),
                "probe_mode": "config-only",
                "fallback_required": True,
            }
            self._run_analyzer(frame=None, status=status)
            return status

        capture = self._ensure_capture()
        if capture is None:
            self._run_analyzer(frame=None)
            return self._status_from_error("offline", "Unable to open RTSP stream")

        ok, frame = capture.read()
        if not ok or frame is None:
            self._consecutive_failures += 1
            self._dropped_frames += 1
            logger.warning("Camera %s frame read failed (%s consecutive failures)", self.camera.id, self._consecutive_failures)
            if self._consecutive_failures >= self._frame_failures_before_reconnect():
                self._release_capture()
            self._run_analyzer(frame=None)
            return self._status_from_error("warning", "Stream opened but no frame was decoded")

        self._consecutive_failures = 0
        self._last_frame_ts = time.time()
        self._last_success_ts = self._last_frame_ts

        process_started = time.time()
        frame = self._prepare_frame(frame)
        status = self._status_from_frame(frame, capture)
        self._run_analyzer(frame=frame, status=status)
        self._processing_ms = round((time.time() - process_started) * 1000, 2)
        self._frames_processed += 1
        status.observations = {
            **(status.observations or {}),
            "processing_ms": self._processing_ms,
            "reconnect_attempts": self._reconnect_attempts,
            "consecutive_failures": self._consecutive_failures,
            "dropped_frames": self._dropped_frames,
            "frames_processed": self._frames_processed,
            "overload": self._processing_ms > (self._sampling_interval_seconds() * 1000 * self._overload_ratio()),
        }
        self._last_process_ts = time.time()
        return status

    def _ensure_capture(self) -> Any:
        if self._capture is not None and self._capture.isOpened():
            return self._capture

        self._release_capture()
        self._reconnect_attempts += 1
        logger.info("Opening RTSP stream for camera %s (attempt %s)", self.camera.id, self._reconnect_attempts)
        capture = cv2.VideoCapture(self.camera.rtsp_url)
        if hasattr(cv2, "CAP_PROP_BUFFERSIZE"):
            try:
                capture.set(cv2.CAP_PROP_BUFFERSIZE, int((self.camera.analysis_config or {}).get("capture_buffer_size", 2)))
            except Exception:  # noqa: BLE001
                pass
        if not capture.isOpened():
            capture.release()
            backoff = min(10.0, 1.5 ** min(self._reconnect_attempts, 6))
            time.sleep(backoff)
            return None

        self._capture = capture
        return self._capture

    def _release_capture(self) -> None:
        if self._capture is not None:
            try:
                self._capture.release()
            except Exception:  # noqa: BLE001
                pass
        self._capture = None

    def _prepare_frame(self, frame: Any) -> Any:
        if cv2 is None or frame is None:
            return frame

        max_width = int((self.camera.analysis_config or {}).get("max_frame_width", 960))
        if max_width > 0 and frame.shape[1] > max_width:
            scale = max_width / frame.shape[1]
            new_height = max(1, int(frame.shape[0] * scale))
            frame = cv2.resize(frame, (max_width, new_height))
        return frame

    def _status_from_frame(self, frame: Any, capture: Any) -> CameraRuntimeStatus:
        now_iso = datetime.now(timezone.utc).isoformat()
        fps = capture.get(cv2.CAP_PROP_FPS) or None
        return CameraRuntimeStatus(
            id=self.camera.id,
            stream_status="online",
            health_message=None,
            last_frame_at=now_iso,
            fps=float(fps) if fps else None,
            analyzer=self._analyzer.__class__.__name__,
            observations={
                "probe_mode": "opencv",
                "resolution": {
                    "width": int(frame.shape[1]),
                    "height": int(frame.shape[0]),
                },
            },
        )

    def _status_from_error(self, stream_status: str, message: str) -> CameraRuntimeStatus:
        stale_for = round(time.time() - self._last_success_ts, 2) if self._last_success_ts else None
        return CameraRuntimeStatus(
            id=self.camera.id,
            stream_status=stream_status,
            health_message=message,
            last_frame_at=datetime.fromtimestamp(self._last_frame_ts, timezone.utc).isoformat() if self._last_frame_ts else None,
            analyzer=self._analyzer.__class__.__name__,
            observations={
                "reconnect_attempts": self._reconnect_attempts,
                "consecutive_failures": self._consecutive_failures,
                "dropped_frames": self._dropped_frames,
                "last_success_age_seconds": stale_for,
            },
        )

    def _sampling_interval_seconds(self) -> float:
        config = self.camera.analysis_config or {}
        if config.get("sampling_interval_seconds") is not None:
            return max(0.2, float(config["sampling_interval_seconds"]))
        sampling_fps = float(config.get("sampling_fps", 1.0))
        return max(0.2, 1.0 / max(0.1, sampling_fps))

    def _frame_failures_before_reconnect(self) -> int:
        config = self.camera.analysis_config or {}
        return max(1, int(config.get("frame_failures_before_reconnect", 3)))

    def _overload_ratio(self) -> float:
        config = self.camera.analysis_config or {}
        return max(1.0, float(config.get("overload_ratio", 1.5)))

    def _run_analyzer(self, frame: Any, status: CameraRuntimeStatus | None = None) -> None:
        result = self._analyzer.analyze(frame=frame, now_ts=time.time())
        if result.events:
            self._pending_events.extend(result.events)

        if status is not None:
            status.observations = {
                **(status.observations or {}),
                **(result.observations or {}),
            }
            status.analyzer = result.observations.get("analyzer") if result.observations else status.analyzer


class CameraRuntimeManager:
    def __init__(self, settings: AgentSettings) -> None:
        self.settings = settings
        self._workers: dict[int, CameraWorker] = {}
        self._overflow_statuses: dict[int, dict[str, Any]] = {}

    def sync_cameras(self, cameras: list[CameraConfig]) -> None:
        active_ids = {camera.id for camera in cameras}

        for worker_id in list(self._workers):
            if worker_id not in active_ids:
                self._workers[worker_id].stop()
                del self._workers[worker_id]

        self._overflow_statuses = {}
        ordered_cameras = sorted(
            cameras,
            key=lambda camera: (
                0 if (camera.analysis_config or {}).get("priority", "normal") == "high" else 1,
                camera.id,
            ),
        )

        for index, camera in enumerate(ordered_cameras):
            if index >= self.settings.max_camera_workers:
                if camera.id in self._workers:
                    self._workers[camera.id].stop()
                    del self._workers[camera.id]
                self._overflow_statuses[camera.id] = asdict(
                    CameraRuntimeStatus(
                        id=camera.id,
                        stream_status="warning",
                        health_message="Worker limit reached on this device",
                        analyzer="not_started",
                        observations={
                            "overload": True,
                            "worker_limit": self.settings.max_camera_workers,
                            "reason": "max_camera_workers_exceeded",
                        },
                    )
                )
                continue

            worker = self._workers.get(camera.id)
            if worker is None:
                worker = CameraWorker(camera, self.settings)
                self._workers[camera.id] = worker
                worker.start()
            else:
                worker.reconfigure(camera)

    def statuses(self) -> list[dict[str, Any]]:
        return [worker.snapshot_status() for worker in self._workers.values()] + list(self._overflow_statuses.values())

    def drain_events(self) -> list[EventPayload]:
        events: list[EventPayload] = []
        for worker in self._workers.values():
            events.extend(worker.drain_events())
        return events

    def stop_all(self) -> None:
        for worker in self._workers.values():
            worker.stop()
        self._workers.clear()
        self._overflow_statuses.clear()
