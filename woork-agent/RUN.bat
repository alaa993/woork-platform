@echo off
setlocal
cd /d "%~dp0"

if not exist ".venv\Scripts\woork-agent.exe" (
  echo Woork Agent is not installed yet. Run INSTALL.bat first.
  pause
  exit /b 1
)

".venv\Scripts\woork-agent.exe" run --config config.json
pause
