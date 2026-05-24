# ═══════════════════════════════════════════════════════════════
#  start-app.ps1  —  Démarrer Dental App
# ═══════════════════════════════════════════════════════════════

$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$MYSQLD     = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYSQL_DATA = "C:\laragon\data\mysql"
$APP_URL    = "http://dental-app-inch.test"
$PROFILE    = "C:\dental-app-browser"   # profil isolé pour l'app

function IsRunning($name) {
    return (Get-Process -Name $name -ErrorAction SilentlyContinue) -ne $null
}

# ─── Démarrer Apache ───────────────────────────────────────────
if (-not (IsRunning "httpd")) {
    Start-Process -FilePath $HTTPD -WindowStyle Hidden
}

# ─── Démarrer MySQL ────────────────────────────────────────────
if (-not (IsRunning "mysqld")) {
    Start-Process -FilePath $MYSQLD -ArgumentList "--datadir=`"$MYSQL_DATA`"" -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

# ─── Attendre que les services soient prêts ────────────────────
$attempts = 0
do {
    Start-Sleep -Milliseconds 500
    $attempts++
} while ((-not (IsRunning "httpd") -or -not (IsRunning "mysqld")) -and $attempts -lt 10)

# ─── Ouvrir l'app en mode fenêtre isolée (sans barre navigateur)
# Essaie Edge (toujours présent sur Windows 11), sinon Chrome, sinon navigateur par défaut
$edge   = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"

$appArgs = "--app=$APP_URL --user-data-dir=`"$PROFILE`""

if (Test-Path $edge) {
    Start-Process $edge $appArgs
} elseif (Test-Path $chrome) {
    Start-Process $chrome $appArgs
} else {
    # Fallback : navigateur par défaut (pas en mode app)
    Start-Process $APP_URL
}
