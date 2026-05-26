from __future__ import annotations

import os
from pathlib import Path

PROGRAM_DATA_CONFIG = Path(r"C:\ProgramData\WoorkAgent\config.json")


def _is_writable_path(config_path: Path) -> bool:
    try:
        config_path.parent.mkdir(parents=True, exist_ok=True)
        probe_path = config_path.parent / ".woork-write-test"
        probe_path.write_text("ok", encoding="utf-8")
        probe_path.unlink(missing_ok=True)
        return True
    except OSError:
        return False


def _local_config_path() -> Path:
    base = Path(os.getenv("LOCALAPPDATA") or Path.home() / "AppData" / "Local")
    return base / "WoorkAgent" / "config.json"


def resolve_config_path(preferred: str | Path) -> Path:
    preferred_path = Path(preferred)

    if preferred_path.exists():
        return preferred_path

    if _is_writable_path(preferred_path):
        return preferred_path

    if os.name == "nt":
        local_config = _local_config_path()
        program_data_config = PROGRAM_DATA_CONFIG

        if local_config.exists() and not program_data_config.exists():
            return local_config

        if _is_writable_path(program_data_config):
            return program_data_config

        if _is_writable_path(local_config):
            return local_config

    preferred_path.parent.mkdir(parents=True, exist_ok=True)
    return preferred_path
