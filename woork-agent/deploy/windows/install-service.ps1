param(
    [switch]$Start,
    [string]$ConfigPath
)

$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$InstallDir = Resolve-Path (Join-Path $BaseDir "..")
$SharedScript = Join-Path $BaseDir "agent-config-paths.ps1"

if (-not (Test-Path $SharedScript)) {
    throw "Missing shared script: $SharedScript"
}

. $SharedScript

$resolvedConfigPath = if ($ConfigPath) {
    Get-AgentConfigPath -PreferredPath $ConfigPath
} else {
    Get-AgentConfigPath
}

Write-Host "Installing Woork Agent service with config: $resolvedConfigPath"
Sync-AgentServiceConfig -InstallDir $InstallDir -ConfigPath $resolvedConfigPath -RestartService:$Start

if ($Start) {
    Write-Host "Woork Agent service installed and started."
} else {
    Write-Host "Woork Agent service installed. Start it from the Woork Agent control app after pairing."
}
