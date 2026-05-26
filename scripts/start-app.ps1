# start-app.ps1 - Demarrer Dental App

$MYSQLADMIN = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe"
$APP_URL    = "http://dental-app-inch.test"
$PROFILE    = "C:\dental-app-browser"

# Demarrer les services Windows
Start-Service "DentalApache" -ErrorAction SilentlyContinue
Start-Service "DentalMySQL"  -ErrorAction SilentlyContinue

# Attendre que MySQL soit pret (max 16 secondes)
$attempts = 0
do {
    Start-Sleep -Milliseconds 800
    $attempts++
    $ping = & $MYSQLADMIN -u root ping 2>$null
    if ($ping -match "alive") { break }
} while ($attempts -lt 20)

# Detecter IP locale pour affichage
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
