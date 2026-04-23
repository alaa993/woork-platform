; Woork Agent Legacy Windows 7 installer script.
; Build on Windows with Python 3.8 and deploy/windows/build-agent-win7.ps1.

#define MyAppName "Woork Agent Legacy"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Woork"
#define MyAppExeName "WoorkAgentControl.exe"

[Setup]
AppId={{615A9341-E6DE-4C6D-9B3C-FEC0A5B2B2F7}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={pf}\WoorkAgentLegacy
DefaultGroupName=Woork Agent Legacy
DisableProgramGroupPage=yes
PrivilegesRequired=admin
MinVersion=6.1
ArchitecturesAllowed=x64
ArchitecturesInstallIn64BitMode=x64
OutputDir=..\..\release
OutputBaseFilename=WoorkAgentSetup-LegacyWin7-{#MyAppVersion}
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
Name: "{group}\Woork Agent Legacy"; Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""
Name: "{commondesktop}\Woork Agent Legacy"; Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""; Tasks: desktopicon

[Tasks]
Name: "desktopicon"; Description: "Create a desktop shortcut"; GroupDescription: "Shortcuts:"

[Run]
Filename: "powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\install-service.ps1"""; Flags: runhidden
Filename: "{app}\WoorkAgentControl.exe"; Parameters: """{commonappdata}\WoorkAgent\config.json"""; Description: "Open Woork Agent Legacy"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\uninstall-service.ps1"""; Flags: runhidden
