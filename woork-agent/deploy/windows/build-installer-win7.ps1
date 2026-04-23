$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$IssFile = Join-Path $BaseDir "WoorkAgentSetup-Win7.iss"
$WinswPath = Join-Path $AgentRoot "deploy\winsw\WinSW-x64.exe"

Set-Location $AgentRoot

& (Join-Path $BaseDir "build-agent-win7.ps1")

if (-not (Test-Path $WinswPath)) {
    throw "WinSW-x64.exe is missing at $WinswPath. Download WinSW x64 and place it there before building the installer."
}

$InnoCandidates = @(
    "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
    "${env:ProgramFiles}\Inno Setup 6\ISCC.exe"
)

$Iscc = $InnoCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $Iscc) {
    throw "Inno Setup 6 was not found. Install it from https://jrsoftware.org/isdl.php then run this script again."
}

& $Iscc $IssFile

Write-Host "Legacy Windows 7 installer build completed under release\"
