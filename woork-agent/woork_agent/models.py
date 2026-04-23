from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any, Optional


@dataclass
class CameraConfig:
    id: int
    organization_id: int
    room_id: Optional[int]
    name: str
    purpose: str
    analysis_mode: str
    rtsp_url: Optional[str]
    roi: Optional[dict[str, Any]] = None
    analysis_config: Optional[dict[str, Any]] = None
    status: Optional[str] = None
    stream_status: Optional[str] = None
    health_message: Optional[str] = None
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
    track_id: Optional[str] = None
    confidence: Optional[float] = None
    meta: Optional[dict[str, Any]] = None


@dataclass
class CameraRuntimeStatus:
    id: int
    stream_status: str
    health_message: Optional[str] = None
    last_frame_at: Optional[str] = None
    fps: Optional[float] = None
    analyzer: Optional[str] = None
    observations: Optional[dict[str, Any]] = None


@dataclass
class RuntimeState:
    token: Optional[str] = None
    agent_device_id: Optional[int] = None
    organization_id: Optional[int] = None
    cameras: list[CameraConfig] = field(default_factory=list)
