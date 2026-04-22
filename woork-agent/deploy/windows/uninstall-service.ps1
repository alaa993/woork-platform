$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$InstallDir = Resolve-Path (Join-Path $BaseDir "..")
$ExePath = Join-Path $InstallDir "WinSW-x64.exe"
$XmlPath = Join-Path $InstallDir "woork-agent-service.xml"

if (-not (Test-Path $ExePath)) {
    throw "WinSW-x64.exe not found at $ExePath."
}

Write-Host "Stopping Woork Agent service..."
& $ExePath stop $XmlPath
Write-Host "Uninstalling Woork Agent service..."
& $ExePath uninstall $XmlPath
Write-Host "Woork Agent service removed."
