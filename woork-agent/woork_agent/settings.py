from __future__ import annotations

import json
from pathlib import Path

from .models import AgentSettings


def load_settings(config_path: str) -> AgentSettings:
    path = Path(config_path)
    data = json.loads(path.read_text())

    return AgentSettings(
        cloud_base_url=data["cloud_base_url"].rstrip("/"),
        device_name=data["device_name"],
        device_uuid=data["device_uuid"],
        os=data.get("os", "windows"),
        version=data.get("version", "1.0.0"),
        state_path=data.get("state_path", "./state/agent-state.sqlite"),
        models_dir=data.get("models_dir", "./models"),
        sync_interval_seconds=int(data.get("sync_interval_seconds", 60)),
        heartbeat_interval_seconds=int(data.get("heartbeat_interval_seconds", 30)),
        runtime_loop_interval_seconds=float(data.get("runtime_loop_interval_seconds", 1.0)),
        max_camera_workers=int(data.get("max_camera_workers", 8)),
        max_queued_events=int(data.get("max_queued_events", 5000)),
        flush_batch_size=int(data.get("flush_batch_size", 100)),
        request_timeout_seconds=int(data.get("request_timeout_seconds", 20)),
        request_retries=int(data.get("request_retries", 3)),
        retry_backoff_seconds=float(data.get("retry_backoff_seconds", 2.0)),
    )
