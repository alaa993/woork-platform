param(
    [Parameter(Mandatory = $true)]
    [string]$InstallDir,
    [Parameter(Mandatory = $true)]
    [string]$ConfigPath,
    [switch]$RestartService
)

$ErrorActionPreference = "Stop"

$SharedScript = Join-Path $PSScriptRoot "agent-config-paths.ps1"
if (-not (Test-Path $SharedScript)) {
    throw "Missing shared script: $SharedScript"
}

. $SharedScript

$resolvedInstallDir = Resolve-Path $InstallDir
if (Test-Path $ConfigPath) {
    $resolvedConfigPath = (Resolve-Path $ConfigPath).Path
} else {
    $resolvedConfigPath = $ConfigPath
}

Sync-AgentServiceConfig -InstallDir $resolvedInstallDir -ConfigPath $resolvedConfigPath -RestartService:$RestartService
