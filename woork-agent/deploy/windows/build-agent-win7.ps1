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
  --exclude-module tkinter `
  --exclude-module _tkinter `
  --collect-all woork_agent `
  agent_entry.py

Write-Host "Legacy Windows 7 build complete. This build excludes Tkinter, OpenCV, and NumPy. Control UI is provided by deploy/windows/control-legacy.ps1."
