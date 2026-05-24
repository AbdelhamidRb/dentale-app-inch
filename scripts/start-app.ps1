# ═══════════════════════════════════════════════════════════════
#  start-app.ps1  —  Démarrer Dental App
# ═══════════════════════════════════════════════════════════════

$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$MYSQLD     = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYSQL_DATA = "C:\laragon\data\mysql"
$CONF_DIR   = "C:\laragon\etc\apache2\sites-enabled"
$CONF_FILE  = "$CONF_DIR\dental-app-inch.conf"
$APP_URL    = "http://dental-app-inch.test"
$PROFILE    = "C:\dental-app-browser"

function IsRunning($name) {
    return (Get-Process -Name $name -ErrorAction SilentlyContinue) -ne $null
}

# ─── Détecter l'IP locale WiFi/Ethernet ───────────────────────
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric |
    Select-Object -First 1).IPAddress

if (-not $localIP) {
    $localIP = "127.0.0.1"
}

# ─── Mettre à jour le VirtualHost Apache avec l'IP actuelle ───
if (Test-Path $CONF_FILE) {
    $conf = Get-Content $CONF_FILE -Raw

    # Bloc IP-based VirtualHost à injecter/remplacer
    $ipBlock = @"

# Accès réseau local (assistante, téléphones)
<VirtualHost ${localIP}:80>
    DocumentRoot "C:/laragon/www/dental-app-inch/public"
    ServerName $localIP
    <Directory "C:/laragon/www/dental-app-inch/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@

    # Supprimer l'ancien bloc IP s'il existe
    $conf = $conf -replace '(?s)\r?\n# Accès réseau local.*?</VirtualHost>', ''

    # Ajouter le nouveau bloc à la fin
    $conf = $conf.TrimEnd() + "`r`n" + $ipBlock

    Set-Content $CONF_FILE $conf -Encoding UTF8
}

# ─── Ouvrir le port 80 dans le Firewall Windows ───────────────
$fwRule = Get-NetFirewallRule -DisplayName "Dental App HTTP" -ErrorAction SilentlyContinue
if (-not $fwRule) {
    New-NetFirewallRule -DisplayName "Dental App HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow | Out-Null
}

# ─── Démarrer Apache ───────────────────────────────────────────
if (-not (IsRunning "httpd")) {
    Start-Process -FilePath $HTTPD -WindowStyle Hidden
} else {
    # Recharger la config pour prendre en compte le nouveau VirtualHost
    Start-Process -FilePath $HTTPD -ArgumentList "-k graceful" -WindowStyle Hidden -Wait
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

# ─── Afficher l'IP réseau dans une popup ──────────────────────
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Application démarrée !`n`nPC dentiste : $APP_URL`nAutres appareils (WiFi) : http://$localIP`n`nPartagez http://$localIP avec l'assistante et les téléphones.",
    "Dental App",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null

# ─── Ouvrir l'app en mode fenêtre isolée (sans barre navigateur)
$edge   = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"

$appArgs = "--app=$APP_URL --user-data-dir=`"$PROFILE`""

if (Test-Path $edge) {
    Start-Process $edge $appArgs
} elseif (Test-Path $chrome) {
    Start-Process $chrome $appArgs
} else {
    Start-Process $APP_URL
}
