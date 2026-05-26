# start-app.ps1 - Demarrer Dental App

$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$MYSQLD     = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYSQLADMIN = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe"
$MYSQL_INI  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini"
$APP_URL    = "http://dental-app-inch.test"
$PROFILE    = "C:\dental-app-browser"

# PATH necessaire pour que Apache charge le module PHP
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;" +
            "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;" +
            "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin;" +
            $env:PATH

# Demarrer Apache si pas en cours
if (-not (Get-Process "httpd" -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $HTTPD -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

# Demarrer MySQL si pas en cours (avec le bon my.ini de Laragon)
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

# Detecter IP locale
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress
if (-not $localIP) { $localIP = "127.0.0.1" }

# Afficher popup
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Application demarree !`n`nPC dentiste  : $APP_URL`nAssistante   : http://$localIP",
    "Dental App",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null

# Ouvrir le navigateur
$edge    = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$chrome  = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$appArgs = "--app=$APP_URL --user-data-dir=`"$PROFILE`""
if (Test-Path $edge)       { Start-Process $edge   $appArgs }
elseif (Test-Path $chrome) { Start-Process $chrome $appArgs }
else                       { Start-Process $APP_URL }
