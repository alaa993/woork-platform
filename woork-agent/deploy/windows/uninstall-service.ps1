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
    throw "WinSW executable not found at $ExePath."
}

Write-Host "Stopping Woork Agent service..."
& $ExePath stop $XmlPath
Write-Host "Uninstalling Woork Agent service..."
& $ExePath uninstall $XmlPath
Write-Host "Woork Agent service removed."
