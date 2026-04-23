$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")

Set-Location $AgentRoot

python -m pip install --upgrade pip
python -m pip install -r requirements-win7.txt
python -m pip install -e .

pyinstaller `
  --name woork-agent `
  --onefile `
  --clean `
  --hidden-import cv2 `
  --hidden-import numpy `
  --collect-all woork_agent `
  woork_agent/cli.py

pyinstaller `
  --name WoorkAgentControl `
  --onefile `
  --windowed `
  --clean `
  --hidden-import tkinter `
  --hidden-import cv2 `
  --hidden-import numpy `
  --collect-all woork_agent `
  woork_agent/control_app.py

Write-Host "Legacy Windows 7 build complete. Output available under dist/woork-agent.exe and dist/WoorkAgentControl.exe"
