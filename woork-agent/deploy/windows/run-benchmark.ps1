param(
    [string]$ConfigPath = "C:\ProgramData\WoorkAgent\config.json",
    [double]$Seconds = 8
)

$ErrorActionPreference = "Stop"

Write-Host "Running Woork Agent synthetic benchmark..."
python -m woork_agent.cli benchmark --config $ConfigPath --seconds $Seconds
