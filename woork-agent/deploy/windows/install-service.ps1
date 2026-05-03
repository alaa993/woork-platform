param(
    [switch]$Start
)

$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$InstallDir = Resolve-Path (Join-Path $BaseDir "..")
$ExePath = if (Test-Path (Join-Path $InstallDir "WinSW.exe")) {
    Join-Path $InstallDir "WinSW.exe"
} elseif (Test-Path (Join-Path $InstallDir "WinSW-x64.exe")) {
    Join-Path $InstallDir "WinSW-x64.exe"
} else {
    Join-Path $InstallDir "WinSW-x86.exe"
}
$XmlPath = Join-Path $InstallDir "woork-agent-service.xml"

if (-not (Test-Path $ExePath)) {
    throw "WinSW executable not found at $ExePath. Place the matching WinSW binary next to woork-agent-service.xml before installing."
}

if (-not (Test-Path "C:\ProgramData\WoorkAgent")) {
    New-Item -ItemType Directory -Path "C:\ProgramData\WoorkAgent" | Out-Null
}

if (-not (Test-Path "C:\ProgramData\WoorkAgent\logs")) {
    New-Item -ItemType Directory -Path "C:\ProgramData\WoorkAgent\logs" | Out-Null
}

Write-Host "Installing Woork Agent service..."
& $ExePath install $XmlPath

if ($Start) {
    & $ExePath start $XmlPath
    Write-Host "Woork Agent service installed and started."
} else {
    Write-Host "Woork Agent service installed. Start it from the Woork Agent control app after pairing."
}
