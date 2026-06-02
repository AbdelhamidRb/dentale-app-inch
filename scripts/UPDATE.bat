@echo off
:: Mise a jour Dental App — fonctionne sur C:\, D:\, etc.
:: Double-cliquer pour mettre a jour

setlocal

:: Detecter chemins depuis l'emplacement du .bat
:: scripts\ -> app\ -> www\ -> laragon\   (3 niveaux)
pushd "%~dp0.."
set APP=%CD%
popd
pushd "%~dp0..\..\..\"
set LARAGON_ROOT=%CD%
popd

set GIT=%LARAGON_ROOT%\bin\git\bin\git.exe
set PATH=%LARAGON_ROOT%\bin\git\bin;%LARAGON_ROOT%\bin\git\usr\bin;%PATH%

:: Detecter PHP
for /d %%d in ("%LARAGON_ROOT%\bin\php\php-8.*") do set PHP=%%d\php.exe

echo.
echo ==================================================
echo    MISE A JOUR - Dental App
echo    App : %APP%
echo ==================================================
echo.

if not exist "%GIT%" ( echo [ERREUR] Git introuvable : %GIT% & pause & exit 1 )
if not exist "%PHP%" ( echo [ERREUR] PHP introuvable & pause & exit 1 )

echo [1/4] Telechargement des mises a jour...
"%GIT%" -C "%APP%" fetch origin main
if errorlevel 1 ( echo [ERREUR] fetch echoue - verifiez internet & pause & exit 1 )
"%GIT%" -C "%APP%" reset --hard origin/main
if errorlevel 1 ( echo [ERREUR] reset echoue & pause & exit 1 )
echo   OK

echo.
echo [2/4] Mise a jour base de donnees...
"%PHP%" "%APP%\artisan" migrate --force
if errorlevel 1 ( echo [ERREUR] Migrations echouees & pause & exit 1 )
echo   OK

echo.
echo [3/4] Optimisation Laravel...
"%PHP%" "%APP%\artisan" route:clear
"%PHP%" "%APP%\artisan" config:cache
"%PHP%" "%APP%\artisan" view:cache
"%PHP%" "%APP%\artisan" cache:clear
echo   OK

echo.
echo [4/4] Redemarrage Apache...
taskkill /f /im httpd.exe >nul 2>&1
timeout /t 2 /nobreak >nul
for /d %%d in ("%LARAGON_ROOT%\bin\apache\*") do (
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
