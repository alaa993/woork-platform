from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any


@dataclass
class CameraConfig:
    id: int
    organization_id: int
    room_id: int | None
    name: str
    purpose: str
    analysis_mode: str
    rtsp_url: str | None
    roi: dict[str, Any] | None = None
    analysis_config: dict[str, Any] | None = None
    status: str | None = None
    stream_status: str | None = None
    health_message: str | None = None
    is_enabled: bool = True


@dataclass
class AgentSettings:
    cloud_base_url: str
    device_name: str
    device_uuid: str
    os: str
    version: str
    state_path: str
    models_dir: str = "./models"
    sync_interval_seconds: int = 60
    heartbeat_interval_seconds: int = 30
    runtime_loop_interval_seconds: float = 1.0
    max_camera_workers: int = 8
    max_queued_events: int = 5000
    flush_batch_size: int = 100
    request_timeout_seconds: int = 20
    request_retries: int = 3
    retry_backoff_seconds: float = 2.0


@dataclass
class EventPayload:
    camera_id: int
    employee_id: int
    room_id: int
    type: str
    started_at: str
    ended_at: str
    track_id: str | None = None
    confidence: float | None = None
    meta: dict[str, Any] | None = None


@dataclass
class CameraRuntimeStatus:
    id: int
    stream_status: str
    health_message: str | None = None
    last_frame_at: str | None = None
    fps: float | None = None
    analyzer: str | None = None
    observations: dict[str, Any] | None = None


@dataclass
class RuntimeState:
    token: str | None = None
    agent_device_id: int | None = None
    organization_id: int | None = None
    cameras: list[CameraConfig] = field(default_factory=list)
