@echo off
setlocal
cd /d "%~dp0"

if not exist ".venv\Scripts\python.exe" (
  echo Woork Agent is not installed yet. Run INSTALL.bat first.
  pause
  exit /b 1
)

".venv\Scripts\python.exe" -m woork_agent.control_app config.json
