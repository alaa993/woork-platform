from __future__ import annotations

import json
import time
import urllib.error
import urllib.request
from dataclasses import fields
from typing import Any, Optional

from .models import AgentSettings, CameraConfig, RuntimeState

try:
    import cv2  # type: ignore
except Exception:  # noqa: BLE001
    cv2 = None


class CloudClient:
    def __init__(self, settings: AgentSettings) -> None:
        self.settings = settings

    def register(self, pairing_token: str) -> dict[str, Any]:
        vision_available = cv2 is not None
        payload = {
            "pairing_token": pairing_token,
            "device_uuid": self.settings.device_uuid,
            "name": self.settings.device_name,
            "version": self.settings.version,
            "os": self.settings.os,
            "capabilities": {
                "rtsp": vision_available,
                "offline_queue": True,
                "multi_camera": True,
                "analyzers": ["interval", "motion_presence", "vision_people"] if vision_available else ["interval"],
                "detectors": ["hog", "opencv_dnn"] if vision_available else [],
                "person_detection": vision_available,
                "simple_tracking": vision_available,
                "phone_detection": False,
            },
        }
        return self._request("POST", "/api/agent/register", payload)

    def fetch_config(self, token: str) -> list[CameraConfig]:
        data = self._request("GET", "/api/agent/config", token=token)
        return [_camera_from_payload(camera) for camera in data.get("cameras", [])]

    def send_heartbeat(
        self,
        token: str,
        runtime_state: RuntimeState,
        stream_statuses: Optional[list[dict[str, Any]]] = None,
    ) -> dict[str, Any]:
        payload = {
            "status": "online",
            "version": self.settings.version,
            "os": self.settings.os,
            "capabilities": {
                "assigned_cameras": len(runtime_state.cameras),
            },
            "cameras": stream_statuses or [],
        }
        return self._request("POST", "/api/agent/heartbeat", payload, token=token)

    def ingest(self, token: str, camera_id: int, events: list[dict[str, Any]]) -> dict[str, Any]:
        payload = {
            "camera_id": camera_id,
            "events": events,
        }
        return self._request("POST", "/api/agent/ingest", payload, token=token)

    def _request(
        self,
        method: str,
        path: str,
        payload: Optional[dict[str, Any]] = None,
        token: Optional[str] = None,
    ) -> dict[str, Any]:
        body = None if payload is None else json.dumps(payload).encode()
        last_error: Optional[Exception] = None

        for attempt in range(1, self.settings.request_retries + 1):
            request = urllib.request.Request(
                url=f"{self.settings.cloud_base_url}{path}",
                method=method,
                data=body,
                headers={
                    "Content-Type": "application/json",
                    **({"Authorization": f"Bearer {token}"} if token else {}),
                },
            )

            try:
                with urllib.request.urlopen(request, timeout=self.settings.request_timeout_seconds) as response:
                    data = response.read().decode()
                    return json.loads(data) if data else {}
            except urllib.error.HTTPError as exc:
                if exc.code < 500:
                    raise RuntimeError(_http_error_message(exc)) from exc
                last_error = exc
            except urllib.error.URLError as exc:
                last_error = exc

            if attempt < self.settings.request_retries:
                time.sleep(self.settings.retry_backoff_seconds * attempt)

        if isinstance(last_error, urllib.error.HTTPError):
            raise RuntimeError(_http_error_message(last_error)) from last_error
        if isinstance(last_error, urllib.error.URLError):
            raise RuntimeError(f"Cloud request unreachable: {last_error.reason}") from last_error
        raise RuntimeError("Cloud request failed for an unknown reason")


def _camera_from_payload(camera: dict[str, Any]) -> CameraConfig:
    allowed = {field.name for field in fields(CameraConfig)}
    filtered = {key: value for key, value in camera.items() if key in allowed}
    return CameraConfig(**filtered)


def _http_error_message(exc: urllib.error.HTTPError) -> str:
    prefix = f"Cloud request failed: {exc.code} {exc.reason}"

    try:
        raw = exc.read().decode("utf-8")
    except Exception:  # noqa: BLE001
        return prefix

    if not raw:
        return prefix

    try:
        payload = json.loads(raw)
    except json.JSONDecodeError:
        return prefix

    if not isinstance(payload, dict):
        return prefix

    for key in ("error", "message", "error_description"):
        value = payload.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()

    errors = payload.get("errors")
    if isinstance(errors, dict):
        for value in errors.values():
            if isinstance(value, list) and value:
                return str(value[0])
            if isinstance(value, str) and value.strip():
                return value.strip()

    return prefix
