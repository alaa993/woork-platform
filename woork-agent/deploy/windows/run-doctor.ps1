param(
    [string]$ConfigPath = "C:\ProgramData\WoorkAgent\config.json"
)

$ErrorActionPreference = "Stop"

Write-Host "Running Woork Agent readiness checks..."
python -m woork_agent.cli doctor --config $ConfigPath
