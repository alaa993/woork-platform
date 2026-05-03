$ErrorActionPreference = "Continue"

$OsVersion = [System.Environment]::OSVersion.Version
$IsWindows7Family = ($OsVersion.Major -lt 10)

if (-not $IsWindows7Family) {
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show(
        "Woork Agent Legacy is for Windows 7 / legacy PCs only.`r`n`r`nThis computer is running Windows 10/11 or newer. Use the standard Windows 10/11 Woork Agent package instead.",
        "Woork Agent Legacy",
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Warning
    ) | Out-Null
    exit 1
}

$InstallDir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ((Split-Path -Leaf $InstallDir) -ieq "scripts") {
    $InstallDir = Resolve-Path (Join-Path $InstallDir "..")
}

$ConfigDir = "C:\ProgramData\WoorkAgent"
$ConfigPath = Join-Path $ConfigDir "config.json"
$AgentExe = Join-Path $InstallDir "woork-agent.exe"
$WinSwExe = Join-Path $InstallDir "WinSW-x64.exe"
$ServiceXml = Join-Path $InstallDir "woork-agent-service.xml"

if (-not (Test-Path $ConfigDir)) {
    New-Item -ItemType Directory -Path $ConfigDir | Out-Null
}

function Get-JsonStringValue {
    param([string]$Text, [string]$Key, [string]$Default)
    $pattern = '"' + [regex]::Escape($Key) + '"\s*:\s*"([^"]*)"'
    $match = [regex]::Match($Text, $pattern)
    if ($match.Success) {
        return $match.Groups[1].Value
    }
    return $Default
}

function Escape-JsonString {
    param([string]$Value)
    if ($null -eq $Value) {
        return ""
    }
    return $Value.Replace("\", "\\").Replace('"', '\"')
}

function Write-AgentConfig {
    param([string]$CloudUrl, [string]$DeviceName, [string]$DeviceUuid)
    if ([string]::IsNullOrEmpty($DeviceUuid) -or $DeviceUuid -eq "replace-with-real-uuid") {
        $DeviceUuid = "woork-" + ([guid]::NewGuid().ToString())
    }
    $CloudUrl = $CloudUrl.TrimEnd("/")
    $content = @"
{
  "cloud_base_url": "$(Escape-JsonString $CloudUrl)",
  "device_name": "$(Escape-JsonString $DeviceName)",
  "device_uuid": "$(Escape-JsonString $DeviceUuid)",
  "os": "windows-7-legacy",
  "version": "1.0.0",
  "state_path": "C:\\ProgramData\\WoorkAgent\\agent-state.sqlite",
  "models_dir": "C:\\ProgramData\\WoorkAgent\\models",
  "sync_interval_seconds": 60,
  "heartbeat_interval_seconds": 30,
  "runtime_loop_interval_seconds": 1.0,
  "max_camera_workers": 2,
  "max_queued_events": 5000,
  "flush_batch_size": 100,
  "request_timeout_seconds": 20,
  "request_retries": 3,
  "retry_backoff_seconds": 2.0
}
"@
    [System.IO.File]::WriteAllText($ConfigPath, $content)
}

function Ensure-AgentConfig {
    if (-not (Test-Path $ConfigPath)) {
        Write-AgentConfig "https://woork.site" "Branch PC" ("woork-" + ([guid]::NewGuid().ToString()))
    }
}

function Load-AgentConfig {
    Ensure-AgentConfig
    $text = [System.IO.File]::ReadAllText($ConfigPath)
    return @{
        CloudUrl = Get-JsonStringValue $text "cloud_base_url" "https://woork.site"
        DeviceName = Get-JsonStringValue $text "device_name" "Branch PC"
        DeviceUuid = Get-JsonStringValue $text "device_uuid" ("woork-" + ([guid]::NewGuid().ToString()))
    }
}

function Run-AgentCommand {
    param([string]$Arguments)
    if (-not (Test-Path $AgentExe)) {
        return "woork-agent.exe was not found: $AgentExe"
    }
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $AgentExe
    $psi.Arguments = $Arguments
    $psi.WorkingDirectory = $InstallDir
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $psi
    [void]$process.Start()
    $stdout = $process.StandardOutput.ReadToEnd()
    $stderr = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($stderr) {
        return ($stdout + "`r`n" + $stderr).Trim()
    }
    if ($stdout) {
        return $stdout.Trim()
    }
    return "Command completed."
}

function Run-ServiceCommand {
    param([string]$Command)
    if (-not (Test-Path $WinSwExe)) {
        return "WinSW-x64.exe was not found: $WinSwExe"
    }
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $WinSwExe
    $psi.Arguments = "$Command `"$ServiceXml`""
    $psi.WorkingDirectory = $InstallDir
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $psi
    [void]$process.Start()
    $stdout = $process.StandardOutput.ReadToEnd()
    $stderr = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    return ($stdout + "`r`n" + $stderr).Trim()
}

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$config = Load-AgentConfig

$form = New-Object System.Windows.Forms.Form
$form.Text = "Woork Agent Legacy"
$form.Size = New-Object System.Drawing.Size(720, 560)
$form.StartPosition = "CenterScreen"

$font = New-Object System.Drawing.Font("Segoe UI", 9)
$form.Font = $font

$cloudLabel = New-Object System.Windows.Forms.Label
$cloudLabel.Text = "Cloud URL"
$cloudLabel.Location = New-Object System.Drawing.Point(16, 18)
$cloudLabel.Size = New-Object System.Drawing.Size(120, 22)
$form.Controls.Add($cloudLabel)

$cloudInput = New-Object System.Windows.Forms.TextBox
$cloudInput.Text = $config.CloudUrl
$cloudInput.Location = New-Object System.Drawing.Point(150, 16)
$cloudInput.Size = New-Object System.Drawing.Size(530, 22)
$form.Controls.Add($cloudInput)

$nameLabel = New-Object System.Windows.Forms.Label
$nameLabel.Text = "Device name"
$nameLabel.Location = New-Object System.Drawing.Point(16, 52)
$nameLabel.Size = New-Object System.Drawing.Size(120, 22)
$form.Controls.Add($nameLabel)

$nameInput = New-Object System.Windows.Forms.TextBox
$nameInput.Text = $config.DeviceName
$nameInput.Location = New-Object System.Drawing.Point(150, 50)
$nameInput.Size = New-Object System.Drawing.Size(530, 22)
$form.Controls.Add($nameInput)

$tokenLabel = New-Object System.Windows.Forms.Label
$tokenLabel.Text = "Pairing token"
$tokenLabel.Location = New-Object System.Drawing.Point(16, 86)
$tokenLabel.Size = New-Object System.Drawing.Size(120, 22)
$form.Controls.Add($tokenLabel)

$tokenInput = New-Object System.Windows.Forms.TextBox
$tokenInput.Location = New-Object System.Drawing.Point(150, 84)
$tokenInput.Size = New-Object System.Drawing.Size(530, 22)
$form.Controls.Add($tokenInput)

$output = New-Object System.Windows.Forms.TextBox
$output.Location = New-Object System.Drawing.Point(16, 170)
$output.Size = New-Object System.Drawing.Size(664, 330)
$output.Multiline = $true
$output.ScrollBars = "Vertical"
$output.ReadOnly = $true
$form.Controls.Add($output)

function Append-Output {
    param([string]$Text)
    $output.AppendText("[" + (Get-Date -Format "HH:mm:ss") + "] " + $Text + "`r`n`r`n")
}

$saveButton = New-Object System.Windows.Forms.Button
$saveButton.Text = "Save Settings"
$saveButton.Location = New-Object System.Drawing.Point(16, 124)
$saveButton.Size = New-Object System.Drawing.Size(105, 30)
$saveButton.Add_Click({
    $current = Load-AgentConfig
    Write-AgentConfig $cloudInput.Text $nameInput.Text $current.DeviceUuid
    Append-Output "Settings saved."
})
$form.Controls.Add($saveButton)

$pairButton = New-Object System.Windows.Forms.Button
$pairButton.Text = "Pair Device"
$pairButton.Location = New-Object System.Drawing.Point(132, 124)
$pairButton.Size = New-Object System.Drawing.Size(105, 30)
$pairButton.Add_Click({
    $current = Load-AgentConfig
    Write-AgentConfig $cloudInput.Text $nameInput.Text $current.DeviceUuid
    if ([string]::IsNullOrEmpty($tokenInput.Text)) {
        [System.Windows.Forms.MessageBox]::Show("Paste the pairing token first.", "Woork Agent Legacy") | Out-Null
        return
    }
    Append-Output (Run-AgentCommand ("pair --config `"$ConfigPath`" --pairing-token `"" + $tokenInput.Text + "`""))
})
$form.Controls.Add($pairButton)

$startButton = New-Object System.Windows.Forms.Button
$startButton.Text = "Start Service"
$startButton.Location = New-Object System.Drawing.Point(248, 124)
$startButton.Size = New-Object System.Drawing.Size(105, 30)
$startButton.Add_Click({ Append-Output (Run-ServiceCommand "start") })
$form.Controls.Add($startButton)

$stopButton = New-Object System.Windows.Forms.Button
$stopButton.Text = "Stop Service"
$stopButton.Location = New-Object System.Drawing.Point(364, 124)
$stopButton.Size = New-Object System.Drawing.Size(105, 30)
$stopButton.Add_Click({ Append-Output (Run-ServiceCommand "stop") })
$form.Controls.Add($stopButton)

$syncButton = New-Object System.Windows.Forms.Button
$syncButton.Text = "Sync"
$syncButton.Location = New-Object System.Drawing.Point(480, 124)
$syncButton.Size = New-Object System.Drawing.Size(80, 30)
$syncButton.Add_Click({ Append-Output (Run-AgentCommand ("sync --config `"$ConfigPath`"")) })
$form.Controls.Add($syncButton)

$heartbeatButton = New-Object System.Windows.Forms.Button
$heartbeatButton.Text = "Heartbeat"
$heartbeatButton.Location = New-Object System.Drawing.Point(572, 124)
$heartbeatButton.Size = New-Object System.Drawing.Size(108, 30)
$heartbeatButton.Add_Click({ Append-Output (Run-AgentCommand ("heartbeat --config `"$ConfigPath`"")) })
$form.Controls.Add($heartbeatButton)

Append-Output "Woork Agent Legacy control is ready. Paste the pairing token, click Pair Device, then Start Service."
[void]$form.ShowDialog()
