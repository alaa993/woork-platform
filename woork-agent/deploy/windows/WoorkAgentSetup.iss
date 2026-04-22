; Woork Agent installer script for Inno Setup.
; Build on Windows after running deploy/windows/build-agent.ps1.

#define MyAppName "Woork Agent"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Woork"
#define MyAppExeName "WoorkAgentControl.exe"

[Setup]
AppId={{8F9AE3EA-6E5E-41D8-A73A-8F7D31E01B4B}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\WoorkAgent
DefaultGroupName=Woork Agent
DisableProgramGroupPage=yes
PrivilegesRequired=admin
OutputDir=..\..\release
OutputBaseFilename=WoorkAgentSetup-{#MyAppVersion}
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Dirs]
Name: "{commonappdata}\WoorkAgent"
Name: "{commonappdata}\WoorkAgent\logs"
Name: "{commonappdata}\WoorkAgent\models"

[Files]
Source: "..\..\dist\woork-agent.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\..\dist\WoorkAgentControl.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\windows\config.production.json"; DestDir: "{commonappdata}\WoorkAgent"; DestName: "config.json"; Flags: onlyifdoesntexist
Source: "..\winsw\woork-agent-service.xml"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\winsw\WinSW-x64.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\windows\install-service.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\windows\uninstall-service.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion

[Icons]
Name: "{group}\Woork Agent"; Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""
Name: "{commondesktop}\Woork Agent"; Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""; Tasks: desktopicon

[Tasks]
Name: "desktopicon"; Description: "Create a desktop shortcut"; GroupDescription: "Shortcuts:"

[Run]
Filename: "powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\install-service.ps1"""; Flags: runhidden
Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""; Description: "Open Woork Agent"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\uninstall-service.ps1"""; Flags: runhidden
