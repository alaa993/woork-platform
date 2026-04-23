# Woork Agent Windows Test Package

This archive is a runnable Windows test package before the final signed
`WoorkAgentSetup.exe` installer is produced.

For the test ZIP package, use `INSTALL.bat`.
For the professional release, the user should receive `WoorkAgentSetup.exe`.

## Requirements

- Windows 10/11 64-bit.
- Python 3.10 or newer.
- Network access to the Woork cloud URL.
- The PC must be on the same local network as the RTSP cameras.

Windows 7 is supported only through the separate Legacy installer:

```text
WoorkAgentSetup-LegacyWin7-1.0.0.exe
```

Use it only for legacy customer PCs. The recommended installer remains the
Windows 10/11 version.

## Install

Double-click:

```text
INSTALL.bat
```

If Windows asks for confirmation, allow it. The script creates a local Python
environment and installs Woork Agent inside this folder.

Manual PowerShell alternative:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
python -m pip install -e .
```

For camera analysis support:

```powershell
python -m pip install opencv-python numpy
```

## Configure

`INSTALL.bat` creates `config.json` automatically if it does not exist.
Open `config.json`, then edit:

- `cloud_url`
- `pairing_token`
- `state_dir`

## Control App

After installation, double-click:

```text
CONTROL.bat
```

The control app shows:

- Server pairing status.
- Agent running/stopped status.
- Camera sync status.
- Buttons for Pair, Doctor, Sync, Heartbeat, Start, and Stop.

## Pair

Double-click `PAIR.bat`, then paste the pairing token from the Woork platform.

Manual alternative:

```powershell
.\.venv\Scripts\woork-agent.exe pair --config config.json --pairing-token YOUR_PAIRING_TOKEN
```

## Diagnose

Double-click `DOCTOR.bat`.

Manual alternative:

```powershell
.\.venv\Scripts\woork-agent.exe doctor --config config.json
.\.venv\Scripts\woork-agent.exe benchmark --config config.json --seconds 8
```

## Run

Double-click `RUN.bat`.

Manual alternative:

```powershell
.\.venv\Scripts\woork-agent.exe run --config config.json
```

## Windows Service

The `deploy/windows` and `deploy/winsw` folders contain service helpers. Use
them after the agent runs correctly from the command line.
