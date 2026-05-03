$ErrorActionPreference = "Stop"

& (Join-Path $PSScriptRoot "build-agent.ps1") -Architecture x86
