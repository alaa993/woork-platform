from __future__ import annotations

import json
import os
import tempfile
import time
from pathlib import Path
from typing import Any, Optional

from .analyzers import build_analyzer
from .model_registry import inspect_models
from .models import AgentSettings, CameraConfig

try:
    import sqlite3
except Exception:  # noqa: BLE001
    sqlite3 = None

try:
    import cv2  # type: ignore
    import numpy as np  # type: ignore
except Exception:  # noqa: BLE001
    cv2 = None
    np = None


def run_doctor(settings: AgentSettings, state_path: Optional[str] = None) -> dict[str, Any]:
    checks: list[dict[str, Any]] = []
    state_file = Path(state_path or settings.state_path)

    checks.append(
        _check(
            "opencv",
            cv2 is not None,
            "OpenCV is available" if cv2 is not None else "OpenCV is not installed",
        )
    )
    checks.append(
        _check(
            "numpy",
            np is not None,
            "NumPy is available" if np is not None else "NumPy is not installed",
        )
    )

    state_dir = state_file.parent
    checks.append(
        _check(
            "state_dir",
            state_dir.exists() or _can_create_dir(state_dir),
            f"State directory is writable: {state_dir}",
        )
    )
    checks.append(
        _check(
            "state_sqlite",
            _state_store_writable(state_dir),
            _state_store_message(state_dir),
        )
    )

    models = inspect_models(settings.models_dir)
    all_models_ok = all(model["model_exists"] is not False for model in models)
    checks.append(
        _check(
            "models_manifest",
            True,
            f"Detected {len(models)} detector bundle(s)",
            details={"models": models},
        )
    )
    checks.append(
        _check(
            "models_files",
            all_models_ok,
            "All declared model bundles have reachable files" if all_models_ok else "One or more model bundles are missing files",
            details={"models": models},
        )
    )

    checks.append(
        _check(
            "request_settings",
            settings.request_retries >= 1 and settings.request_timeout_seconds >= 5,
            f"timeout={settings.request_timeout_seconds}s retries={settings.request_retries}",
        )
    )
    checks.append(
        _check(
            "worker_limit",
            settings.max_camera_workers >= 1,
            f"max_camera_workers={settings.max_camera_workers}",
        )
    )

    return {
        "ok": all(check["ok"] for check in checks if check["name"] != "models_manifest"),
        "checks": checks,
    }


def run_benchmark(settings: AgentSettings, seconds: float = 5.0, width: int = 960, height: int = 540) -> dict[str, Any]:
    if cv2 is None or np is None:
        return {
            "ok": False,
            "error": "OpenCV and NumPy are required for benchmarking",
        }

    camera = CameraConfig(
        id=1,
        organization_id=1,
        room_id=1,
        name="benchmark-camera",
        purpose="desk",
        analysis_mode="desk_monitoring",
        rtsp_url="benchmark://synthetic",
        roi={"work_zone": []},
        analysis_config={
            "analyzer": "vision_people",
            "fallback_analyzer": "motion_presence",
            "detector": "auto",
            "detector_bundle": None,
            "assigned_employee_id": 1,
            "sampling_interval_seconds": 1.0,
            "idle_after_seconds": 300,
            "away_after_seconds": 180,
            "motion_threshold": 12,
            "min_motion_ratio": 0.01,
            "tracking_max_distance": 90,
            "tracking_max_missing_frames": 6,
            "phone_event_type": "phone",
        },
        is_enabled=True,
    )

    analyzer = build_analyzer(camera)
    started = time.perf_counter()
    frames = 0
    last_observations: dict[str, Any] = {}

    while (time.perf_counter() - started) < seconds:
        frame = _synthetic_frame(width, height, frames)
        result = analyzer.analyze(frame=frame, now_ts=time.time())
        last_observations = result.observations or {}
        frames += 1

    elapsed = max(0.001, time.perf_counter() - started)
    fps = frames / elapsed
    recommended = max(1, min(settings.max_camera_workers, int(fps / 1.5)))

    return {
        "ok": True,
        "frames_processed": frames,
        "elapsed_seconds": round(elapsed, 3),
        "synthetic_fps": round(fps, 2),
        "recommended_camera_workers": recommended,
        "detector": last_observations.get("detector"),
        "analyzer": last_observations.get("analyzer"),
        "notes": {
            "benchmark_type": "synthetic_frame_loop",
            "frame_size": {"width": width, "height": height},
            "warning": "Synthetic benchmark is only an estimate; validate with real RTSP cameras on the target device.",
        },
    }


def _synthetic_frame(width: int, height: int, index: int) -> Any:
    frame = np.zeros((height, width, 3), dtype=np.uint8)
    offset = (index * 7) % max(1, width - 140)
    cv2.rectangle(frame, (offset, 60), (offset + 110, 320), (255, 255, 255), -1)
    cv2.rectangle(frame, (offset + 35, 25), (offset + 75, 75), (255, 255, 255), -1)
    if index % 5 == 0:
        cv2.rectangle(frame, (offset + 80, 170), (offset + 120, 240), (255, 255, 255), -1)
    return frame


def _can_create_dir(path: Path) -> bool:
    try:
        path.mkdir(parents=True, exist_ok=True)
        return True
    except Exception:  # noqa: BLE001
        return False


def _state_store_writable(state_dir: Path) -> bool:
    if sqlite3 is None:
        return _json_state_writable(state_dir)
    return _sqlite_writable(state_dir)


def _state_store_message(state_dir: Path) -> str:
    if sqlite3 is None:
        return f"SQLite unavailable on this system; JSON fallback state store is writable in {state_dir}"
    if _sqlite_writable(state_dir):
        return f"SQLite state store is writable in {state_dir}"
    return f"SQLite state store is not writable in {state_dir}"


def _sqlite_writable(state_dir: Path) -> bool:
    temp_path = None
    try:
        state_dir.mkdir(parents=True, exist_ok=True)
        fd, raw_path = tempfile.mkstemp(dir=state_dir, suffix=".sqlite")
        os.close(fd)
        temp_path = Path(raw_path)

        conn = sqlite3.connect(str(temp_path))
        conn.execute("CREATE TABLE smoke (id INTEGER PRIMARY KEY)")
        conn.execute("INSERT INTO smoke DEFAULT VALUES")
        conn.commit()
        conn.close()
        return True
    except Exception:  # noqa: BLE001
        return False
    finally:
        if temp_path and temp_path.exists():
            try:
                temp_path.unlink()
            except Exception:  # noqa: BLE001
                pass


def _json_state_writable(state_dir: Path) -> bool:
    temp_path = None
    try:
        state_dir.mkdir(parents=True, exist_ok=True)
        fd, raw_path = tempfile.mkstemp(dir=state_dir, suffix=".json")
        os.close(fd)
        temp_path = Path(raw_path)
        temp_path.write_text('{"ok": true}', encoding="utf-8")
        return True
    except Exception:  # noqa: BLE001
        return False
    finally:
        if temp_path and temp_path.exists():
            try:
                temp_path.unlink()
            except Exception:  # noqa: BLE001
                pass


def _check(name: str, ok: bool, message: str, details: Optional[dict[str, Any]] = None) -> dict[str, Any]:
    payload = {
        "name": name,
        "ok": ok,
        "message": message,
    }
    if details:
        payload["details"] = details
    return payload


def print_json(payload: dict[str, Any]) -> None:
    print(json.dumps(payload, indent=2))
