from __future__ import annotations

import json
from pathlib import Path
from typing import Any


def load_model_manifest(models_dir: str) -> dict[str, Any]:
    base_dir = Path(models_dir)
    manifest_path = base_dir / "manifest.json"
    if not manifest_path.exists():
        return {"models": {}}
    return json.loads(manifest_path.read_text())


def resolve_detector_bundle(
    analysis_config: dict[str, Any] | None,
    models_dir: str,
) -> dict[str, Any]:
    config = dict(analysis_config or {})
    bundle_name = config.get("detector_bundle")
    if not bundle_name:
        return config

    manifest = load_model_manifest(models_dir)
    bundle = (manifest.get("models") or {}).get(str(bundle_name))
    if not isinstance(bundle, dict):
        return config

    base_dir = Path(models_dir)
    for key in ("dnn_model_path", "dnn_config_path", "dnn_labels_path"):
        value = bundle.get(key)
        if value and not config.get(key):
            config[key] = str((base_dir / value).resolve())

    if bundle.get("detector") and not config.get("detector"):
        config["detector"] = bundle["detector"]

    if bundle.get("dnn_labels") and not config.get("dnn_labels"):
        config["dnn_labels"] = bundle["dnn_labels"]

    return config


def inspect_models(models_dir: str) -> list[dict[str, Any]]:
    manifest = load_model_manifest(models_dir)
    models = manifest.get("models") or {}
    base_dir = Path(models_dir)
    results: list[dict[str, Any]] = []

    for name, config in models.items():
        if not isinstance(config, dict):
            continue
        results.append(
            {
                "name": name,
                "detector": config.get("detector", "opencv_dnn"),
                "model_exists": _exists(base_dir, config.get("dnn_model_path")),
                "config_exists": _exists(base_dir, config.get("dnn_config_path")),
                "labels_exists": _exists(base_dir, config.get("dnn_labels_path")),
                "labels": config.get("dnn_labels"),
            }
        )

    return results


def _exists(base_dir: Path, relative_path: str | None) -> bool | None:
    if not relative_path:
        return None
    return (base_dir / relative_path).exists()
