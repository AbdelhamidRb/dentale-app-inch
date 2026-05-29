@echo off
chcp 65001 > nul
echo.
echo =============================================
echo    Installation Dental App
echo =============================================
echo.
echo Telechargement du script d'installation...

powershell.exe -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/AbdelhamidRb/dentale-app-inch/main/scripts/install.ps1' -OutFile '%~dp0install.ps1' -UseBasicParsing"

if not exist "%~dp0install.ps1" (
    echo.
    echo [ERREUR] Telechargement echoue. Verifiez la connexion internet.
    pause
    exit /b 1
)

powershell.exe -ExecutionPolicy Bypass -File "%~dp0install.ps1"

if %ERRORLEVEL% neq 0 (
    echo.
    echo [ERREUR] L'installation a echoue. Voir les messages ci-dessus.
    pause
    exit /b 1
)
