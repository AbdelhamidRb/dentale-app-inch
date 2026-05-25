@echo off
echo Installation Dental App en cours...
echo Telechargement du script d'installation...

powershell.exe -ExecutionPolicy Bypass -Command ^
  "Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/AbdelhamidRb/dentale-app-inch/main/scripts/install.ps1' -OutFile '%~dp0install.ps1' -UseBasicParsing"

powershell.exe -ExecutionPolicy Bypass -File "%~dp0install.ps1"
