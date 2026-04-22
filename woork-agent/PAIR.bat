@echo off
setlocal
cd /d "%~dp0"

if not exist ".venv\Scripts\woork-agent.exe" (
  echo Woork Agent is not installed yet. Run INSTALL.bat first.
  pause
  exit /b 1
)

if "%~1"=="" (
  set /p PAIRING_TOKEN=Enter pairing token: 
) else (
  set PAIRING_TOKEN=%~1
)

".venv\Scripts\woork-agent.exe" pair --config config.json --pairing-token "%PAIRING_TOKEN%"
pause
