function Test-Win7ServicePack1 {
    try {
        $os = Get-WmiObject Win32_OperatingSystem -ErrorAction Stop
        return ($os.Version -like "6.1*") -and ($os.CSDVersion -match "Service Pack 1")
    } catch {
        return $false
    }
}

function Test-Win7UpdateKb2533623 {
    try {
        if (Get-HotFix -Id KB2533623 -ErrorAction SilentlyContinue) {
            return $true
        }
    } catch {
        # Get-HotFix is unavailable on some images.
    }

    $cbsRoot = "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Component Based Servicing\Packages"
    if (Test-Path $cbsRoot) {
        $match = Get-ChildItem $cbsRoot -ErrorAction SilentlyContinue |
            Where-Object { $_.PSChildName -match "KB2533623" } |
            Select-Object -First 1
        if ($match) {
            return $true
        }
    }

    return $false
}

function Test-VcRuntime140 {
    param([string]$InstallDir)

    $candidates = @(
        (Join-Path $env:SystemRoot "System32\vcruntime140.dll"),
        (Join-Path $InstallDir "vcruntime140.dll")
    )
    foreach ($path in $candidates) {
        if (Test-Path $path) {
            return $true
        }
    }
    return $false
}

function Get-Win7PrerequisiteReport {
    param([string]$InstallDir)

    $checks = @(
        [PSCustomObject]@{
            Name = "Windows 7 SP1"
            Ok = (Test-Win7ServicePack1)
            Fix = "Install Windows 7 Service Pack 1 from Windows Update."
        },
        [PSCustomObject]@{
            Name = "Update KB2533623"
            Ok = (Test-Win7UpdateKb2533623)
            Fix = "Install KB2533623 (required for Python on Windows 7), then reboot."
        },
        [PSCustomObject]@{
            Name = "VC++ 2015-2019 runtime (vcruntime140.dll)"
            Ok = (Test-VcRuntime140 -InstallDir $InstallDir)
            Fix = "Install Microsoft Visual C++ 2015-2019 Redistributable (x64), then reboot."
        }
    )

    return $checks
}

function Format-Win7PrerequisiteReport {
    param([object[]]$Checks)

    $lines = New-Object System.Collections.Generic.List[string]
    foreach ($check in $Checks) {
        $status = if ($check.Ok) { "OK" } else { "MISSING" }
        $lines.Add("$status - $($check.Name)")
        if (-not $check.Ok) {
            $lines.Add("      Fix: $($check.Fix)")
        }
    }
    return ($lines -join "`r`n")
}

function Get-Win7SocketFailureHint {
    return @(
        "Woork Agent could not load Python networking (_socket).",
        "",
        "This Legacy build must be installed from WoorkAgentSetup-LegacyWin7-*.exe",
        "(not the standard Windows 10/11 installer).",
        "",
        "On Windows 7 SP1 also install:",
        "  1) KB2533623",
        "  2) Microsoft Visual C++ 2015-2019 Redistributable (x64)",
        "",
        "Reboot after installing updates, then click Test Agent again."
    ) -join "`r`n"
}
