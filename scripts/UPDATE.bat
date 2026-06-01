@echo off
:: Mise a jour Dental App
:: Double-cliquer pour mettre a jour l'application

setlocal

set APP=%LARAGON_ROOT%\www\dental-app-inch
set GIT=%LARAGON_ROOT%\bin\git\bin\git.exe
set PATH=%LARAGON_ROOT%\bin\git\bin;%LARAGON_ROOT%\bin\git\usr\bin;%PATH%

:: Detecter PHP
for /d %%d in (%LARAGON_ROOT%\bin\php\php-8.*) do set PHP=%%d\php.exe

:: Detecter Node/npm (cherche npm.cmd dans tous les sous-dossiers)
for /r "%LARAGON_ROOT%\bin\nodejs" %%f in (npm.cmd) do set NPM=%%f

echo.
echo ==================================================
echo    MISE A JOUR - Dental App
echo ==================================================
echo.

:: Verifier les outils
if not exist "%GIT%"  ( echo [ERREUR] Git introuvable & pause & exit 1 )
if not exist "%PHP%"  ( echo [ERREUR] PHP introuvable & pause & exit 1 )

echo [1/5] Telechargement des mises a jour...
"%GIT%" -C "%APP%" pull origin main
if errorlevel 1 ( echo [ERREUR] git pull echoue - verifiez internet & pause & exit 1 )
echo   OK

echo.
echo [2/5] Mise a jour base de donnees...
"%PHP%" "%APP%\artisan" migrate --force
if errorlevel 1 ( echo [ERREUR] Migrations echouees & pause & exit 1 )
echo   OK

echo.
echo [3/5] Reconstruction interface...
if defined NPM (
    "%NPM%" --prefix "%APP%" run build
    if errorlevel 1 ( echo [WARN] npm build echoue - interface peut etre ancienne )
) else (
    echo   [WARN] Node.js introuvable - interface non reconstruite
)
echo   OK

echo.
echo [4/5] Optimisation Laravel...
"%PHP%" "%APP%\artisan" optimize
"%PHP%" "%APP%\artisan" cache:clear
echo   OK

echo.
echo [5/5] Redemarrage Apache...
taskkill /f /im httpd.exe >nul 2>&1
timeout /t 2 /nobreak >nul
for /d %%d in (%LARAGON_ROOT%\bin\apache\*) do (
    if exist "%%d\bin\httpd.exe" start "" /b "%%d\bin\httpd.exe"
)
timeout /t 2 /nobreak >nul
tasklist | find "httpd.exe" >nul 2>&1
if errorlevel 1 ( echo [WARN] Apache non demarre - ouvrez Laragon et cliquez Start All )
else echo   OK

echo.
echo ==================================================
echo   MISE A JOUR TERMINEE !
echo   Appuyez sur Ctrl+Shift+R dans le navigateur
echo ==================================================
echo.
pause
