from __future__ import annotations

import logging
import time
from datetime import datetime, timezone

from .api_client import CloudClient
from .camera_runtime import CameraRuntimeManager
from .model_registry import resolve_detector_bundle
from .models import EventPayload
from .settings import load_settings
from .storage import LocalStateStore

logger = logging.getLogger(__name__)


class AgentRuntime:
    def __init__(self, config_path: str) -> None:
        self.settings = load_settings(config_path)
        self.store = LocalStateStore(self.settings.state_path)
        self.client = CloudClient(self.settings)
        self.state = self.store.load_runtime_state()
        self.camera_runtime = CameraRuntimeManager(self.settings)

    def pair(self, pairing_token: str) -> None:
        result = self.client.register(pairing_token)
        self.state.token = result["token"]
        self.state.organization_id = result["organization_id"]
        self.state.agent_device_id = result["agent_device_id"]
        self.store.persist_runtime_state(self.state)
        logger.info("Agent paired successfully as device id %s", self.state.agent_device_id)

    def sync_config(self) -> None:
        self._require_token()
        cameras = self.client.fetch_config(self.state.token)
        for camera in cameras:
            camera.analysis_config = resolve_detector_bundle(camera.analysis_config, self.settings.models_dir)
        self.state.cameras = cameras
        self.store.persist_runtime_state(self.state)
        self.camera_runtime.sync_cameras(self.state.cameras)
        logger.info("Fetched %s cameras from cloud", len(self.state.cameras))

    def heartbeat_once(self) -> None:
        self._require_token()
        statuses = self.camera_runtime.statuses()
        if not statuses:
            now_iso = datetime.now(timezone.utc).isoformat()
            for camera in self.state.cameras:
                statuses.append(
                    {
                        "id": camera.id,
                        "stream_status": "pending",
                        "health_message": "Camera worker not initialized yet",
                        "last_frame_at": now_iso,
                    }
                )
        self.client.send_heartbeat(self.state.token, self.state, statuses)
        logger.info("Heartbeat sent with %s camera statuses", len(statuses))

    def queue_sample_event(self, camera_id: int, employee_id: int, room_id: int, event_type: str) -> None:
        now = datetime.now(timezone.utc)
        payload = EventPayload(
            camera_id=camera_id,
            employee_id=employee_id,
            room_id=room_id,
            type=event_type,
            started_at=now.isoformat(),
            ended_at=now.isoformat(),
            meta={"source": "manual-cli"},
        )
        self.store.enqueue_event(payload)
        logger.info("Queued sample event for camera=%s employee=%s", camera_id, employee_id)

    def flush_events(self) -> None:
        self._require_token()
        grouped: dict[int, list[dict]] = {}
        ids_by_camera: dict[int, list[int]] = {}

        for row_id, payload in self.store.list_queued_events(limit=self.settings.flush_batch_size):
            camera_id = int(payload["camera_id"])
            grouped.setdefault(camera_id, []).append(
                {
                    "employee_id": payload["employee_id"],
                    "room_id": payload["room_id"],
                    "type": payload["type"],
                    "track_id": payload.get("track_id"),
                    "confidence": payload.get("confidence"),
                    "started_at": payload["started_at"],
                    "ended_at": payload["ended_at"],
                    "meta": payload.get("meta"),
                }
            )
            ids_by_camera.setdefault(camera_id, []).append(row_id)

        for camera_id, events in grouped.items():
            self.client.ingest(self.state.token, camera_id, events)
            self.store.delete_queued_events(ids_by_camera[camera_id])
            logger.info("Uploaded %s events for camera %s", len(events), camera_id)

    def collect_runtime_events(self) -> None:
        events = self.camera_runtime.drain_events()
        trimmed = 0
        if events:
            current_count = self.store.queued_events_count()
            projected = current_count + len(events)
            if projected > self.settings.max_queued_events:
                trimmed = self.store.trim_oldest_queued_events(
                    max(0, self.settings.max_queued_events - len(events))
                )
        for event in events:
            self.store.enqueue_event(event)
        if events:
            logger.info(
                "Collected %s runtime-generated events%s",
                len(events),
                f"; trimmed {trimmed} oldest queued events" if trimmed else "",
            )

    def run_forever(self) -> None:
        self._require_token()
        last_sync = 0.0
        last_heartbeat = 0.0

        try:
            while True:
                now = time.time()
                try:
                    if now - last_sync >= self.settings.sync_interval_seconds:
                        self.sync_config()
                        last_sync = now

                    if now - last_heartbeat >= self.settings.heartbeat_interval_seconds:
                        self.heartbeat_once()
                        last_heartbeat = now

                    self.collect_runtime_events()
                    self.flush_events()
                except Exception as exc:  # noqa: BLE001
                    logger.exception("Runtime loop error: %s", exc)

                time.sleep(self.settings.runtime_loop_interval_seconds)
        finally:
            self.camera_runtime.stop_all()

    def _require_token(self) -> None:
        if not self.state.token:
            raise RuntimeError("Agent is not paired yet. Run `woork-agent pair` first.")


def setup_logging(verbose: bool = False) -> None:
    logging.basicConfig(
        level=logging.DEBUG if verbose else logging.INFO,
        format="%(asctime)s %(levelname)s %(name)s %(message)s",
    )


def setup_file_logging(log_path: str, verbose: bool = False) -> None:
    from logging.handlers import RotatingFileHandler
    from pathlib import Path

    Path(log_path).parent.mkdir(parents=True, exist_ok=True)

    root = logging.getLogger()
    root.setLevel(logging.DEBUG if verbose else logging.INFO)
    root.handlers.clear()

    formatter = logging.Formatter("%(asctime)s %(levelname)s %(name)s %(message)s")

    file_handler = RotatingFileHandler(
        log_path,
        maxBytes=5 * 1024 * 1024,
        backupCount=5,
        encoding="utf-8",
    )
    file_handler.setFormatter(formatter)
    root.addHandler(file_handler)

    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    root.addHandler(console_handler)
