$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")

Set-Location $AgentRoot

python -m pip install --upgrade pip
python -m pip install -r requirements-win7.txt
python -m pip install -e . --no-deps

pyinstaller `
  --name woork-agent `
  --onefile `
  --clean `
  --exclude-module multiprocessing `
  --exclude-module cv2 `
  --exclude-module numpy `
  --collect-all woork_agent `
  woork_agent/cli.py

pyinstaller `
  --name WoorkAgentControl `
  --onefile `
  --windowed `
  --clean `
  --hidden-import tkinter `
  --exclude-module multiprocessing `
  --exclude-module cv2 `
  --exclude-module numpy `
  --collect-all woork_agent `
  woork_agent/control_app.py

Write-Host "Legacy Windows 7 build complete. This build excludes OpenCV/NumPy and runs config, pairing, heartbeat, and interval fallback analysis only."
