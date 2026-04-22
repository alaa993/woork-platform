$ErrorActionPreference = "Stop"

param(
    [Parameter(Mandatory = $true)]
    [string]$Version
)

$BaseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AgentRoot = Resolve-Path (Join-Path $BaseDir "..\..")
$DistDir = Join-Path $AgentRoot "dist"
$ReleaseDir = Join-Path $AgentRoot "release"
$PackageName = "woork-agent-windows-x64-$Version"
$PackagePath = Join-Path $ReleaseDir $PackageName

if (-not (Test-Path (Join-Path $DistDir "woork-agent.exe"))) {
    throw "woork-agent.exe not found. Run build-agent.ps1 first."
}

if (-not (Test-Path (Join-Path $DistDir "WoorkAgentControl.exe"))) {
    throw "WoorkAgentControl.exe not found. Run build-agent.ps1 first."
}

New-Item -ItemType Directory -Force -Path $ReleaseDir | Out-Null
Remove-Item -Recurse -Force $PackagePath -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $PackagePath | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $PackagePath "winsw") | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $PackagePath "scripts") | Out-Null

Copy-Item (Join-Path $DistDir "woork-agent.exe") (Join-Path $PackagePath "woork-agent.exe")
Copy-Item (Join-Path $DistDir "WoorkAgentControl.exe") (Join-Path $PackagePath "WoorkAgentControl.exe")
Copy-Item (Join-Path $AgentRoot "config.example.json") (Join-Path $PackagePath "config.example.json")
Copy-Item (Join-Path $AgentRoot "README.md") (Join-Path $PackagePath "README.md")
Copy-Item (Join-Path $AgentRoot "deploy\winsw\woork-agent-service.xml") (Join-Path $PackagePath "winsw\woork-agent-service.xml")
Copy-Item (Join-Path $AgentRoot "deploy\windows\install-service.ps1") (Join-Path $PackagePath "scripts\install-service.ps1")
Copy-Item (Join-Path $AgentRoot "deploy\windows\uninstall-service.ps1") (Join-Path $PackagePath "scripts\uninstall-service.ps1")

$ZipPath = Join-Path $ReleaseDir "$PackageName.zip"
Remove-Item $ZipPath -Force -ErrorAction SilentlyContinue
Compress-Archive -Path (Join-Path $PackagePath "*") -DestinationPath $ZipPath

Get-FileHash -Algorithm SHA256 $ZipPath | Format-List
Write-Host "Release package created at $ZipPath"
