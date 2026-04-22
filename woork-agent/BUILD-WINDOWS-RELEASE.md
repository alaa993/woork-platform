# Build WoorkAgentSetup.exe

## Option A: Build With GitHub Actions

Use this when you do not have a Windows machine.

This repository includes:

```text
.github/workflows/build-woork-agent.yml
```

Steps:

1. Push the project to GitHub.
2. Open the repository on GitHub.
3. Go to `Actions`.
4. Choose `Build Woork Agent Installer`.
5. Click `Run workflow`.
6. Wait until the build completes.
7. Open the completed workflow run.
8. Download the artifact named `WoorkAgentSetup-1.0.0`.

The downloaded artifact contains:

```text
WoorkAgentSetup-1.0.0.exe
```

Upload that file to:

```text
httpdocs/public/downloads/WoorkAgentSetup-1.0.0.exe
```

Then update/publish the Agent Release in Woork Cloud:

```text
artifact_path = downloads/WoorkAgentSetup-1.0.0.exe
```

## Option B: Build On Windows Manually

Build this release on a Windows machine. The macOS/Linux workspace can prepare
the source, but the final customer installer must be produced on Windows.

## Requirements

- Windows 10/11
- Python 3.11+
- Inno Setup 6
- WinSW x64 executable

## Prepare WinSW

Download the WinSW x64 executable and place it here:

```text
deploy\winsw\WinSW-x64.exe
```

The installer build intentionally fails if this file is missing. Shipping
without it would install the control app but not the background service.

## Build

Open PowerShell:

```powershell
cd woork-agent
.\deploy\windows\build-installer.ps1
```

The script will:

- Build `dist\woork-agent.exe`
- Build `dist\WoorkAgentControl.exe`
- Compile `release\WoorkAgentSetup-1.0.0.exe`

## Upload To Woork Cloud

Upload:

```text
release\WoorkAgentSetup-1.0.0.exe
```

to:

```text
httpdocs/public/downloads/WoorkAgentSetup-1.0.0.exe
```

Then publish/update the Agent Release in the admin panel with:

```text
version: 1.0.0
channel: stable
platform: windows-x64
artifact_path: downloads/WoorkAgentSetup-1.0.0.exe
```

## Subscriber Flow

The subscriber should only see:

1. Download Woork Agent.
2. Run `WoorkAgentSetup.exe`.
3. Open Woork Agent.
4. Paste Pairing Token.
5. Click Pair Device.
6. Click Start Agent.
7. Verify Server and Cameras status.
