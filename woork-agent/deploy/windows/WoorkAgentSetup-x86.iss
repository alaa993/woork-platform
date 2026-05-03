; Woork Agent installer script for Inno Setup.
; Build on Windows after running deploy/windows/build-agent.ps1 -Architecture x86.

#ifndef MyBinarySuffix
  #define MyBinarySuffix "-x86"
#endif
#ifndef MyWinSWFile
  #define MyWinSWFile "WinSW-x86.exe"
#endif

#define MyAppName "Woork Agent"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Woork"
#define MyAppExeName "WoorkAgentControl{#MyBinarySuffix}.exe"

[Setup]
AppId={{22E7A749-9D05-449A-A18D-22B5B34F773E}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf32}\WoorkAgent
DefaultGroupName=Woork Agent
DisableProgramGroupPage=yes
PrivilegesRequired=admin
MinVersion=10.0
ArchitecturesAllowed=x86compatible
OutputDir=..\..\release
OutputBaseFilename=WoorkAgentSetup-x86-{#MyAppVersion}
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
Source: "..\..\dist\woork-agent{#MyBinarySuffix}.exe"; DestDir: "{app}"; DestName: "woork-agent.exe"; Flags: ignoreversion
Source: "..\..\dist\WoorkAgentControl{#MyBinarySuffix}.exe"; DestDir: "{app}"; DestName: "WoorkAgentControl.exe"; Flags: ignoreversion
Source: "..\windows\config.production.json"; DestDir: "{commonappdata}\WoorkAgent"; DestName: "config.json"; Flags: onlyifdoesntexist
Source: "..\winsw\woork-agent-service.xml"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\winsw\{#MyWinSWFile}"; DestDir: "{app}"; DestName: "WinSW.exe"; Flags: ignoreversion
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
