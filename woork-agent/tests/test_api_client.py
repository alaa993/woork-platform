from __future__ import annotations

import io
import json
import urllib.error

from woork_agent.api_client import _camera_from_payload, _http_error_message
from woork_agent.models import CameraConfig


def test_http_error_message_uses_api_error_field() -> None:
    body = json.dumps({"ok": False, "error": "This device UUID is already paired to another organization."}).encode()
    exc = urllib.error.HTTPError(
        url="https://example.test/api/agent/register",
        code=409,
        msg="Conflict",
        hdrs=None,
        fp=io.BytesIO(body),
    )

    assert _http_error_message(exc) == "This device UUID is already paired to another organization."


def test_camera_from_payload_ignores_nested_room() -> None:
    camera = _camera_from_payload(
        {
            "id": 1,
            "organization_id": 10,
            "room_id": 3,
            "name": "Cam A",
            "purpose": "desk",
            "analysis_mode": "desk_monitoring",
            "rtsp_url": "rtsp://camera/stream",
            "room": {"id": 3, "name": "Main Office"},
        }
    )

    assert isinstance(camera, CameraConfig)
    assert camera.id == 1
    assert camera.name == "Cam A"
