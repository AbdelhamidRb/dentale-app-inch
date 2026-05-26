# start-app.ps1 - Demarrer Dental App

$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$MYSQLD     = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYSQLADMIN = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe"
$MYSQL_DATA = "C:\laragon\data\mysql"
$APP_URL    = "http://dental-app-inch.test"
$PROFILE    = "C:\dental-app-browser"

function IsRunning($name) {
    return (Get-Process -Name $name -ErrorAction SilentlyContinue) -ne $null
}

# Detecter l'IP locale pour affichage
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric |
    Select-Object -First 1).IPAddress
if (-not $localIP) { $localIP = "127.0.0.1" }

# Demarrer Apache si pas en cours
if (-not (IsRunning "httpd")) {
    Start-Process -FilePath $HTTPD -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

# Demarrer MySQL si pas en cours
if (-not (IsRunning "mysqld")) {
    Start-Process -FilePath $MYSQLD -ArgumentList "--datadir=`"$MYSQL_DATA`"" -WindowStyle Hidden
    $attempts = 0
    do {
        Start-Sleep -Milliseconds 800
        $attempts++
        $ping = & $MYSQLADMIN -u root ping 2>$null
        if ($ping -match "alive") { break }
    } while ($attempts -lt 20)
}

# Afficher popup
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Application demarree !`n`nPC dentiste  : $APP_URL`nAssistante   : http://$localIP",
    "Dental App",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null

# Ouvrir le navigateur
$edge   = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$appArgs = "--app=$APP_URL --user-data-dir=`"$PROFILE`""
if (Test-Path $edge)        { Start-Process $edge   $appArgs }
elseif (Test-Path $chrome)  { Start-Process $chrome $appArgs }
else                        { Start-Process $APP_URL }
