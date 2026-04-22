$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$IssFile = Join-Path $BaseDir "WoorkAgentSetup.iss"
$WinswPath = Join-Path $AgentRoot "deploy\winsw\WinSW-x64.exe"

Set-Location $AgentRoot

if (-not (Test-Path (Join-Path $AgentRoot "dist\woork-agent.exe")) -or
    -not (Test-Path (Join-Path $AgentRoot "dist\WoorkAgentControl.exe"))) {
    & (Join-Path $BaseDir "build-agent.ps1")
}

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

Write-Host "Installer build completed under release\"
