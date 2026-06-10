$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$SpecFile = Join-Path $BaseDir "woork-agent-win7.spec"
$PythonVersion = [System.Version]::Parse((& python -c "import sys; print('.'.join(map(str, sys.version_info[:3])))"))
$PythonArch = if ([Environment]::Is64BitProcess) { "x64" } else { "x86" }

if ($PythonVersion.Major -ne 3 -or $PythonVersion.Minor -ne 8) {
    throw "Windows 7 legacy builds must be produced with Python 3.8.x exactly. Current Python version is $PythonVersion."
}

Set-Location $AgentRoot

python -m pip install --upgrade pip
python -m pip install -r requirements-win7.txt
python -m pip install -e . --no-deps

if (Test-Path "dist\woork-agent") {
    Remove-Item "dist\woork-agent" -Recurse -Force
}
if (Test-Path "build\woork-agent") {
    Remove-Item "build\woork-agent" -Recurse -Force
}

pyinstaller --clean --noconfirm $SpecFile

$DistDir = Join-Path $AgentRoot "dist\woork-agent"
if (-not (Test-Path (Join-Path $DistDir "woork-agent.exe"))) {
    throw "Expected dist\woork-agent\woork-agent.exe was not produced."
}

$PythonHome = (& python -c "import sys; print(sys.base_prefix)").Trim()
$ExtraDlls = @(
    "python3.dll",
    "python38.dll",
    "vcruntime140.dll",
    "vcruntime140_1.dll"
)
foreach ($dll in $ExtraDlls) {
    $source = Join-Path $PythonHome $dll
    if (Test-Path $source) {
        Copy-Item $source (Join-Path $DistDir $dll) -Force
    }
}

$SocketPyd = Join-Path $PythonHome "DLLs\_socket.pyd"
if (Test-Path $SocketPyd) {
    Copy-Item $SocketPyd (Join-Path $DistDir "_socket.pyd") -Force
}

$BuildInfo = @{
    platform = "windows-7-legacy"
    python_version = $PythonVersion.ToString()
    python_arch = $PythonArch
    pyinstaller = (& python -c "import PyInstaller; print(PyInstaller.__version__)").Trim()
    build_mode = "onedir"
    built_at = (Get-Date).ToUniversalTime().ToString("o")
} | ConvertTo-Json -Depth 3

Set-Content -Path (Join-Path $DistDir "build-info.json") -Value $BuildInfo -Encoding UTF8

Write-Host "Legacy Windows 7 onedir build complete for $PythonArch Python 3.8."
Write-Host "Output: dist\woork-agent\"
Write-Host "This build excludes Tkinter, OpenCV, and NumPy. Control UI: deploy/windows/control-legacy.ps1"
