function Test-AgentDirectoryWritable {
    param([string]$Path)
    try {
        if (-not (Test-Path $Path)) {
            New-Item -ItemType Directory -Path $Path -Force | Out-Null
        }
        $probe = Join-Path $Path ".woork-write-test"
        [System.IO.File]::WriteAllText($probe, "ok")
        Remove-Item $probe -Force -ErrorAction SilentlyContinue
        return $true
    } catch {
        return $false
    }
}

function Get-AgentProgramDataDir {
    return "C:\ProgramData\WoorkAgent"
}

function Get-AgentLocalConfigDir {
    $localRoot = [Environment]::GetFolderPath("LocalApplicationData")
    return Join-Path $localRoot "WoorkAgent"
}

function Get-AgentConfigDirectory {
    $programDataDir = Get-AgentProgramDataDir
    if (Test-AgentDirectoryWritable $programDataDir) {
        return $programDataDir
    }

    $localDir = Get-AgentLocalConfigDir
    if (Test-AgentDirectoryWritable $localDir) {
        return $localDir
    }

    throw "No writable configuration directory was found for Woork Agent."
}

function Get-AgentConfigPath {
    param([string]$PreferredPath)

    if ($PreferredPath) {
        $preferredDir = Split-Path -Parent $PreferredPath
        if ((Test-Path $PreferredPath) -or (Test-AgentDirectoryWritable $preferredDir)) {
            return $PreferredPath
        }
    }

    $programDataConfig = Join-Path (Get-AgentProgramDataDir) "config.json"
    $localConfig = Join-Path (Get-AgentLocalConfigDir) "config.json"

    if ((Test-Path $localConfig) -and -not (Test-Path $programDataConfig)) {
        return (Resolve-Path $localConfig).Path
    }

    if (Test-AgentDirectoryWritable (Get-AgentProgramDataDir)) {
        return $programDataConfig
    }

    if (Test-AgentDirectoryWritable (Get-AgentLocalConfigDir)) {
        return $localConfig
    }

    throw "No writable configuration path was found for Woork Agent."
}

function Initialize-AgentConfigDirectory {
    param([string]$ConfigPath)

    $configDir = Split-Path -Parent $ConfigPath
    foreach ($subDir in @("logs", "models")) {
        $target = Join-Path $configDir $subDir
        if (-not (Test-Path $target)) {
            New-Item -ItemType Directory -Path $target -Force | Out-Null
        }
    }
}

function Get-AgentWinSwExecutable {
    param([string]$InstallDir)

    foreach ($name in @("WinSW.exe", "WinSW-x64.exe", "WinSW-x86.exe")) {
        $candidate = Join-Path $InstallDir $name
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    throw "WinSW executable not found in $InstallDir"
}

function Get-AgentServiceTemplateXml {
    param([string]$InstallDir)

    $template = Join-Path $InstallDir "woork-agent-service.xml"
    if (-not (Test-Path $template)) {
        throw "Service template not found: $template"
    }

    return $template
}

function Get-AgentServiceRuntimeXml {
    param([string]$InstallDir)

    return Join-Path $InstallDir "woork-agent-service.runtime.xml"
}

function New-AgentServiceRuntimeXml {
    param(
        [string]$InstallDir,
        [string]$ConfigPath
    )

    $templatePath = Get-AgentServiceTemplateXml -InstallDir $InstallDir
    $runtimePath = Get-AgentServiceRuntimeXml -InstallDir $InstallDir
    $content = [System.IO.File]::ReadAllText($templatePath)
    $escapedConfig = [System.Security.SecurityElement]::Escape($ConfigPath)
    $content = [regex]::Replace(
        $content,
        "<arguments>.*?</arguments>",
        "<arguments>run --config $escapedConfig</arguments>"
    )
    [System.IO.File]::WriteAllText($runtimePath, $content)
    return $runtimePath
}

function Sync-AgentServiceConfig {
    param(
        [string]$InstallDir,
        [string]$ConfigPath,
        [switch]$RestartService
    )

    Initialize-AgentConfigDirectory -ConfigPath $ConfigPath
    $runtimeXml = New-AgentServiceRuntimeXml -InstallDir $InstallDir -ConfigPath $ConfigPath
    $winSw = Get-AgentWinSwExecutable -InstallDir $InstallDir

    $service = Get-Service -Name "woork-agent" -ErrorAction SilentlyContinue
    $wasRunning = $service -and $service.Status -eq "Running"

    if ($service) {
        & $winSw stop $runtimeXml 2>$null
        & $winSw uninstall $runtimeXml 2>$null
    }

    & $winSw install $runtimeXml

    if ($RestartService -or $wasRunning) {
        & $winSw start $runtimeXml
    }

    return $runtimeXml
}
