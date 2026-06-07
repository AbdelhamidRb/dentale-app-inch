# start-app.ps1 - Demarrer Dental App

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
# Detection automatique des chemins Laragon
$phpDir    = Get-ChildItem "$LARAGON_ROOT\bin\php"    -Directory | Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1
$mysqlDir  = Get-ChildItem "$LARAGON_ROOT\bin\mysql"  -Directory | Sort-Object Name -Descending | Select-Object -First 1
$apacheDir = Get-ChildItem "$LARAGON_ROOT\bin\apache" -Directory | Sort-Object Name -Descending | Select-Object -First 1

$HTTPD      = Join-Path $apacheDir.FullName "bin\httpd.exe"
$MYSQLD     = Join-Path $mysqlDir.FullName  "bin\mysqld.exe"
$MYSQLADMIN = Join-Path $mysqlDir.FullName  "bin\mysqladmin.exe"
$MYSQL_INI  = Join-Path $mysqlDir.FullName  "my.ini"
$VHOST_FILE = "$LARAGON_ROOT\etc\apache2\sites-enabled\dental-app-inch.conf"
$APP_URL    = "http://dental-app-inch.test"
$BROWSER_PROFILE = "$(Split-Path $LARAGON_ROOT -Qualifier)\dental-app-browser"

# PATH necessaire pour que Apache charge le module PHP
$env:PATH = "$($phpDir.FullName);" +
            "$($mysqlDir.FullName)\bin;" +
            "$($apacheDir.FullName)\bin;" +
            $env:PATH

# Detecter IP locale
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress
if (-not $localIP) { $localIP = "127.0.0.1" }

# Ecrire le VirtualHost avec l'IP actuelle (avant de demarrer Apache)
# L'Alias /storage evite le probleme de junction Windows (Apache ne suit pas les junctions
# dans mod_rewrite, ce qui ferait rediriger les PDF/images vers l'app au lieu de les servir)
$storagePath = ($APP_ROOT -replace '\\', '/') + "/storage/app/public"
$docRoot     = ($LARAGON_ROOT -replace '\\', '/') + "/www/dental-app-inch/public"
@"
<VirtualHost *:80>
    ServerName dental-app-inch.test
    ServerAlias dental.local dental $localIP
    DocumentRoot "$docRoot"

    Alias /storage "$storagePath"
    <Directory "$storagePath">
        AllowOverride None
        Require all granted
        Options FollowSymLinks
    </Directory>

    <Directory "$docRoot">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@ | Set-Content $VHOST_FILE -Encoding UTF8

# Demarrer Apache si pas en cours
if (-not (Get-Process "httpd" -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $HTTPD -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

# Demarrer MySQL si pas en cours
if (-not (Get-Process "mysqld" -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $MYSQLD -ArgumentList "--defaults-file=`"$MYSQL_INI`"" -WindowStyle Hidden
}

# Attendre que MySQL soit pret (max 20 secondes)
$attempts = 0
do {
    Start-Sleep -Milliseconds 800
    $attempts++
    $ping = & $MYSQLADMIN -u root ping 2>$null
    if ($ping -match "alive") { break }
} while ($attempts -lt 25)

# Afficher popup
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Application demarree !`n`nPC dentiste  : $APP_URL`nAssistante   : http://$localIP",
    "Dental App",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null

# Ouvrir le navigateur
$edge64  = "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
$edge86  = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$chrome  = "$env:ProgramFiles\Google\Chrome\Application\chrome.exe"
$appArgs = "--app=$APP_URL --user-data-dir=`"$BROWSER_PROFILE`""
$edge    = if (Test-Path $edge64) { $edge64 } elseif (Test-Path $edge86) { $edge86 } else { $null }
if ($edge)                 { Start-Process $edge   $appArgs }
elseif (Test-Path $chrome) { Start-Process $chrome $appArgs }
else                       { Start-Process $APP_URL }
