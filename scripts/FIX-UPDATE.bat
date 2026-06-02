@echo off
chcp 65001 >nul
echo.
echo ==============================================
echo   Correctif Mise a Jour - Dental App
echo ==============================================
echo.

:: Trouver Laragon sur C: D: E: etc.
set LARAGON=
set GIT=
for %%d in (C D E F G H) do (
    if exist "%%d:\laragon\bin\git\bin\git.exe" (
        set LARAGON=%%d:\laragon
        set GIT=%%d:\laragon\bin\git\bin\git.exe
    )
)

if "%GIT%"=="" (
    echo [ERREUR] Git introuvable. Verifiez que Laragon est installe.
    pause
    exit /b 1
)

set APP=%LARAGON%\www\dental-app-inch

echo Laragon detecte : %LARAGON%
echo Application     : %APP%
echo.

if not exist "%APP%\.git" (
    echo [ERREUR] L'application est introuvable dans %APP%
    pause
    exit /b 1
)

echo Telechargement de la mise a jour depuis GitHub...
"%GIT%" -C "%APP%" pull origin main
if errorlevel 1 (
    echo [ERREUR] Impossible de recuperer la mise a jour.
    echo Verifiez la connexion internet.
    pause
    exit /b 1
)

echo.
echo [OK] Mise a jour recuperee avec succes.
echo.
echo Vous pouvez maintenant utiliser UPDATE.bat normalement.
echo.
pause
