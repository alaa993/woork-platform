# Build WoorkAgentSetup.exe

## Option A: Build With GitHub Actions

Use this when you do not have a Windows machine.

This repository includes two workflows:

```text
.github/workflows/build-woork-agent.yml
.github/workflows/build-woork-agent-win7.yml
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

## Legacy Windows 7 Build

Windows 7 is not the primary supported platform. Build it only for legacy
customer PCs that cannot be upgraded.

GitHub Actions:

1. Open `Actions`.
2. Choose `Build Woork Agent Legacy Windows 7 Installer`.
3. Click `Run workflow`.
4. Download the artifact named `WoorkAgentSetup-LegacyWin7-1.0.0`.

The downloaded artifact contains:

```text
WoorkAgentSetup-LegacyWin7-1.0.0.exe
```

Upload it to:

```text
httpdocs/public/downloads/WoorkAgentSetup-LegacyWin7-1.0.0.exe
```

The platform shows this as a secondary legacy download only when the file
exists. It does not replace the main Windows 10/11 installer.

## Option B: Build On Windows Manually

Build this release on a Windows machine. The macOS/Linux workspace can prepare
the source, but the final customer installer must be produced on Windows.

## Requirements

- Windows 10/11
- Python 3.11+
- Inno Setup 6
- WinSW x64 executable

The generated production installer targets Windows 10/11 64-bit. Windows 7 is
not supported by this build because the packaged Python/OpenCV runtime depends
on modern Windows API sets that are not present on Windows 7.

For Windows 7 legacy builds, use:

```powershell
.\deploy\windows\build-installer-win7.ps1
```

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
2. Run `WoorkAgentSetup.exe` on Windows 10/11 64-bit.
3. Open Woork Agent.
4. Paste Pairing Token.
5. Click Pair Device.
6. Click Start Agent.
7. Verify Server and Cameras status.
