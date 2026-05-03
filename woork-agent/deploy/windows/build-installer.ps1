param(
    [ValidateSet("x64", "x86")]
    [string]$Architecture = "x64"
)

$ErrorActionPreference = "Stop"

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$IssFile = if ($Architecture -eq "x86") { Join-Path $BaseDir "WoorkAgentSetup-x86.iss" } else { Join-Path $BaseDir "WoorkAgentSetup.iss" }
$WinSwFile = if ($Architecture -eq "x86") { "WinSW-x86.exe" } else { "WinSW-x64.exe" }
$WinswPath = Join-Path $AgentRoot "deploy\winsw\$WinSwFile"
$AgentExeName = if ($Architecture -eq "x86") { "woork-agent-x86.exe" } else { "woork-agent.exe" }
$ControlExeName = if ($Architecture -eq "x86") { "WoorkAgentControl-x86.exe" } else { "WoorkAgentControl.exe" }
$BinarySuffix = if ($Architecture -eq "x86") { "-x86" } else { "" }

Set-Location $AgentRoot

if (-not (Test-Path (Join-Path $AgentRoot "dist\$AgentExeName")) -or
    -not (Test-Path (Join-Path $AgentRoot "dist\$ControlExeName"))) {
    & (Join-Path $BaseDir "build-agent.ps1") -Architecture $Architecture
}

if (-not (Test-Path $WinswPath)) {
    throw "$WinSwFile is missing at $WinswPath. Download the matching WinSW binary and place it there before building the installer."
}

$InnoCandidates = @(
    "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
    "${env:ProgramFiles}\Inno Setup 6\ISCC.exe"
)

$Iscc = $InnoCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $Iscc) {
    throw "Inno Setup 6 was not found. Install it from https://jrsoftware.org/isdl.php then run this script again."
}

& $Iscc "/DMyBinarySuffix=$BinarySuffix" "/DMyWinSWFile=$WinSwFile" $IssFile

Write-Host "Installer build completed under release\ for $Architecture"
