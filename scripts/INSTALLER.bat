@echo off
chcp 65001 > nul
echo.
echo =============================================
echo    Installation Dental App
echo =============================================
echo.

:: Verifier que install.ps1 est present dans le meme dossier
if not exist "%~dp0install.ps1" (
    echo [ERREUR] install.ps1 introuvable dans le dossier.
    echo Assurez-vous que INSTALLER.bat et install.ps1 sont dans le meme dossier.
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
