from __future__ import annotations

from pathlib import Path

from woork_agent.paths import resolve_config_path


def test_resolve_config_path_keeps_existing_file(tmp_path: Path) -> None:
    config = tmp_path / "config.json"
    config.write_text("{}", encoding="utf-8")

    assert resolve_config_path(config) == config


def test_resolve_config_path_uses_writable_requested_directory(tmp_path: Path) -> None:
    config = tmp_path / "nested" / "config.json"

    resolved = resolve_config_path(config)

    assert resolved == config
    assert resolved.parent.exists()
