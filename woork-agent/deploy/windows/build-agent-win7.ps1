$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$PythonVersion = [System.Version]::Parse((& python -c "import sys; print('.'.join(map(str, sys.version_info[:3])))"))
$PythonArch = if ([Environment]::Is64BitProcess) { "x64" } else { "x86" }

if ($PythonVersion.Major -ne 3 -or $PythonVersion.Minor -ne 8) {
    throw "Windows 7 legacy builds must be produced with Python 3.8.x exactly. Current Python version is $PythonVersion."
}

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

Write-Host "Legacy Windows 7 build complete for $PythonArch Python 3.8. This build excludes Tkinter, OpenCV, and NumPy. Control UI is provided by deploy/windows/control-legacy.ps1."
