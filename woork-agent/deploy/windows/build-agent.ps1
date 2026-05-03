$ErrorActionPreference = "Stop"

param(
    [ValidateSet("x64", "x86")]
    [string]$Architecture = "x64"
)

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$PythonArch = if ([Environment]::Is64BitProcess) { "x64" } else { "x86" }

if ($PythonArch -ne $Architecture) {
    throw "This script must run under a $Architecture Python runtime. Current Python architecture is $PythonArch."
}

Set-Location $AgentRoot

python -m pip install --upgrade pip
python -m pip install pyinstaller
python -m pip install -e .

$AgentExeName = if ($Architecture -eq "x86") { "woork-agent-x86" } else { "woork-agent" }
$ControlExeName = if ($Architecture -eq "x86") { "WoorkAgentControl-x86" } else { "WoorkAgentControl" }

pyinstaller `
  --name $AgentExeName `
  --onefile `
  --clean `
  --hidden-import cv2 `
  --hidden-import numpy `
  --collect-all woork_agent `
  agent_entry.py

pyinstaller `
  --name $ControlExeName `
  --onefile `
  --windowed `
  --clean `
  --hidden-import tkinter `
  --hidden-import cv2 `
  --hidden-import numpy `
  --collect-all woork_agent `
  control_entry.py

Write-Host "Build complete for $Architecture. Output available under dist/$AgentExeName.exe and dist/$ControlExeName.exe"
