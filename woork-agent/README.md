# Woork Agent

Woork Agent runs inside the subscriber network, pulls RTSP streams locally, syncs configuration from Woork Cloud, and uploads heartbeat plus analytics events back to the platform.

## Runtime goals

- Pair a local Windows/Linux device with Woork Cloud
- Fetch assigned camera configuration
- Persist local token/config state
- Send heartbeat updates
- Queue and upload analytics events
- Operate even if the cloud is temporarily unreachable

## Camera analyzers

`Woork Agent` now supports analyzer selection per camera through `analysis_config`:

- `interval`: compatibility analyzer that emits periodic state events
- `motion_presence`: OpenCV-based analyzer that infers `work_active`, `idle`, and `away` from frame motion
- `vision_people`: OpenCV HOG-based people detection with simple centroid tracking and ROI-aware state inference

Recommended `analysis_config` example:

```json
{
  "analyzer": "vision_people",
  "fallback_analyzer": "motion_presence",
  "detector": "auto",
  "detector_bundle": null,
  "dnn_model_path": null,
  "dnn_config_path": null,
  "dnn_labels_path": null,
  "assigned_employee_id": 12,
  "healthcheck_interval_seconds": 10,
  "min_event_gap_seconds": 60,
  "idle_after_seconds": 300,
  "away_after_seconds": 180,
  "motion_threshold": 12,
  "min_motion_ratio": 0.01,
  "tracking_max_distance": 90,
  "tracking_max_missing_frames": 6,
  "presence_event_type": "work_active",
  "phone_event_type": "phone",
  "idle_event_type": "idle",
  "away_event_type": "away"
}
```

Notes:

- `assigned_employee_id` is required for the analyzer to emit events.
- `vision_people` uses OpenCV's built-in HOG people detector, so detection quality is suitable for baseline desk and entrance monitoring but not yet a full deep-learning model.
- If you provide an `opencv_dnn` model with labels that include `cell phone` or `phone`, the same analyzer can emit `phone` events.
- With the default `hog` detector, `phone detection` remains unavailable.
- You can register local detector bundles through `models/manifest.json` and reference them with `detector_bundle`.
- When OpenCV is not installed, `vision_people` can fall back to `motion_presence`, then `interval`, depending on `fallback_analyzer`.
- The analyzer name and observations are included in heartbeat payloads and camera diagnostics.

## Local detector bundles

The agent can resolve local DNN bundles automatically from `models/manifest.json`.

Example:

1. Copy `models/manifest.example.json` to `models/manifest.json`
2. Place model files under the configured `models_dir`
3. Set this in a camera:

```json
{
  "analyzer": "vision_people",
  "detector_bundle": "coco_ssd_phone"
}
```

Inspect available local bundles:

```bash
woork-agent inspect-models --config config.json
```

## Readiness and performance checks

Before connecting real cameras on a customer device, run:

```bash
woork-agent doctor --config config.json
woork-agent benchmark --config config.json --seconds 8
```

`doctor` checks:

- OpenCV / NumPy availability
- writable local SQLite state path
- detector bundle files under `models_dir`
- timeout / retry / worker-limit settings

`benchmark` returns:

- synthetic FPS estimate
- recommended camera worker count for this device
- active analyzer / detector path used during the test

Use these two commands on the real client PC before pairing multiple cameras.

## Quick start

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -e .
cp config.example.json config.json
woork-agent pair --config config.json --pairing-token PAIR-XXXXXX-XXXXXX
woork-agent run --config config.json
```

## Packaging notes

- Windows packaging can be done with `pyinstaller`
- The generated executable should later be wrapped into `WoorkAgentSetup.exe`
- For production Windows service mode, ship the executable with NSSM or WinSW

## Windows service deployment

The repository now includes production-oriented WinSW artifacts:

- `deploy/winsw/woork-agent-service.xml`
- `deploy/windows/install-service.ps1`
- `deploy/windows/uninstall-service.ps1`
- `deploy/windows/run-doctor.ps1`
- `deploy/windows/run-benchmark.ps1`

Recommended flow:

1. Build `woork-agent.exe` using PyInstaller.
2. Place the binary next to the WinSW executable in `deploy/winsw/`.
3. Copy `config.json` to `C:\ProgramData\WoorkAgent\config.json`.
4. Run `install-service.ps1` as Administrator.
5. Run `run-doctor.ps1` and `run-benchmark.ps1` before assigning multiple real cameras.

## Build and release workflow

Windows packaging helpers are included:

- `deploy/windows/build-agent.ps1`
- `deploy/windows/package-release.ps1`
- `release-manifest.example.json`

Suggested release process:

1. Run `build-agent.ps1`.
2. Run `package-release.ps1 -Version 1.0.0`.
3. Upload the generated ZIP from the Laravel admin release form.
4. Publish the release in `Agent Downloads`.
