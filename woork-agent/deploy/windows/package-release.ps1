$ErrorActionPreference = "Stop"

param(
    [Parameter(Mandatory = $true)]
    [string]$Version,
    [ValidateSet("windows-x64", "windows-x86")]
    [string]$Platform = "windows-x64"
)

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$DistDir = Join-Path $AgentRoot "dist"
$ReleaseDir = Join-Path $AgentRoot "release"
$BinarySuffix = if ($Platform -eq "windows-x86") { "-x86" } else { "" }
$AgentExeName = "woork-agent$BinarySuffix.exe"
$ControlExeName = "WoorkAgentControl$BinarySuffix.exe"
$PackageName = "woork-agent-$Platform-$Version"
$PackagePath = Join-Path $ReleaseDir $PackageName
$WinSwFile = if ($Platform -eq "windows-x86") { "WinSW-x86.exe" } else { "WinSW-x64.exe" }
$WinSwPath = Join-Path $AgentRoot "deploy\winsw\$WinSwFile"

if (-not (Test-Path (Join-Path $DistDir $AgentExeName))) {
    throw "$AgentExeName not found. Run the matching build-agent script first."
}

if (-not (Test-Path (Join-Path $DistDir $ControlExeName))) {
    throw "$ControlExeName not found. Run the matching build-agent script first."
}

if (-not (Test-Path $WinSwPath)) {
    throw "$WinSwFile not found. Place the matching WinSW binary under deploy\\winsw before packaging."
}

New-Item -ItemType Directory -Force -Path $ReleaseDir | Out-Null
Remove-Item -Recurse -Force $PackagePath -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $PackagePath | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $PackagePath "winsw") | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $PackagePath "scripts") | Out-Null

Copy-Item (Join-Path $DistDir $AgentExeName) (Join-Path $PackagePath "woork-agent.exe")
Copy-Item (Join-Path $DistDir $ControlExeName) (Join-Path $PackagePath "WoorkAgentControl.exe")
Copy-Item (Join-Path $AgentRoot "config.example.json") (Join-Path $PackagePath "config.example.json")
Copy-Item (Join-Path $AgentRoot "README.md") (Join-Path $PackagePath "README.md")
Copy-Item (Join-Path $AgentRoot "PLATFORM-SUPPORT.md") (Join-Path $PackagePath "PLATFORM-SUPPORT.md")
Copy-Item (Join-Path $AgentRoot "deploy\winsw\woork-agent-service.xml") (Join-Path $PackagePath "winsw\woork-agent-service.xml")
Copy-Item $WinSwPath (Join-Path $PackagePath "winsw\WinSW.exe")
Copy-Item (Join-Path $AgentRoot "deploy\windows\install-service.ps1") (Join-Path $PackagePath "scripts\install-service.ps1")
Copy-Item (Join-Path $AgentRoot "deploy\windows\uninstall-service.ps1") (Join-Path $PackagePath "scripts\uninstall-service.ps1")

$ZipPath = Join-Path $ReleaseDir "$PackageName.zip"
Remove-Item $ZipPath -Force -ErrorAction SilentlyContinue
Compress-Archive -Path (Join-Path $PackagePath "*") -DestinationPath $ZipPath

Get-FileHash -Algorithm SHA256 $ZipPath | Format-List
Write-Host "Release package created at $ZipPath"
