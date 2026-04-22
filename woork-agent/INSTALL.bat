@echo off
setlocal
cd /d "%~dp0"

echo.
echo Woork Agent installer
echo =====================
echo.

where python >nul 2>nul
if errorlevel 1 (
  echo Python was not found.
  echo Install Python 3.11 or newer from https://www.python.org/downloads/
  echo Make sure to enable "Add python.exe to PATH", then run this file again.
  pause
  exit /b 1
)

if not exist ".venv" (
  echo Creating local Python environment...
  python -m venv .venv
  if errorlevel 1 goto failed
)

call ".venv\Scripts\activate.bat"

echo Upgrading pip...
python -m pip install --upgrade pip
if errorlevel 1 goto failed

echo Installing Woork Agent...
python -m pip install -e .
if errorlevel 1 goto failed

if not exist "config.json" (
  copy "config.example.json" "config.json" >nul
  echo Created config.json from config.example.json
)

echo.
echo Installation completed.
echo Edit config.json, then run PAIR.bat with your pairing token.
echo.
pause
exit /b 0

:failed
echo.
echo Installation failed. Review the error above.
pause
exit /b 1
