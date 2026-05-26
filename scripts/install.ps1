# Installation automatique Dental App
# Prerequis : Laragon installe dans C:\laragon
# Usage : clic droit INSTALLER.bat -> Executer en tant qu'administrateur

$GITHUB_REPO = "AbdelhamidRb/dentale-app-inch"
$BRANCH      = "main"
$APP_DIR     = "C:\laragon\www\dental-app-inch"
$DB_NAME     = "dental_db_inch"
$DB_USER     = "root"
$DB_PASS     = ""
$APP_URL     = "http://dental-app-inch.test"

$PHP        = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$COMPOSER   = "C:\laragon\bin\composer\composer.phar"
$GIT        = "C:\laragon\bin\git\bin\git.exe"
$MYSQL      = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$MYSQLD     = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYSQLADMIN = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe"
$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$MYSQL_DATA = "C:\laragon\data\mysql-8.4"

# Ajouter Git au PATH pour que Composer puisse l'utiliser
$env:Path = "C:\laragon\bin\git\bin;C:\laragon\bin\git\usr\bin;$env:Path"

function Step($n, $t, $msg) { Write-Host ""; Write-Host "[$n/$t] $msg" -ForegroundColor Cyan }
function OK($msg)   { Write-Host "  OK  $msg" -ForegroundColor Green }
function ERR($msg)  { Write-Host ""; Write-Host "  [ERREUR] $msg" -ForegroundColor Red; Read-Host "Appuyez sur Entree pour quitter"; exit 1 }
function WARN($msg) { Write-Host "  ATTENTION  $msg" -ForegroundColor Yellow }

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

# Detecter le Bureau (OneDrive ou classique)
$DESKTOP = "$env:USERPROFILE\OneDrive\Desktop"
if (-not (Test-Path $DESKTOP)) { $DESKTOP = "$env:USERPROFILE\Desktop" }
if (-not (Test-Path $DESKTOP)) { $DESKTOP = [Environment]::GetFolderPath("Desktop") }

Clear-Host
Write-Host "=================================================="
Write-Host "   Installation Dental App"
Write-Host "=================================================="
Write-Host ""

# ── Informations des comptes ───────────────────────────────────
Write-Host "Compte DENTISTE :" -ForegroundColor Cyan
$DENTIST_NAME  = Read-Host "  Nom complet (ex: Dr. Salim Benali)"
$DENTIST_EMAIL = Read-Host "  Email (ex: salim@gmail.com)"
$DENTIST_PASS  = Read-Host "  Mot de passe (min. 8 caracteres)"
if ($DENTIST_NAME -eq "")  { $DENTIST_NAME  = "Dr. Dentiste" }
if ($DENTIST_EMAIL -eq "") { $DENTIST_EMAIL = "dentiste@cabinet.ma" }
if ($DENTIST_PASS -eq "")  { $DENTIST_PASS  = "ChangeMe2024!" }

Write-Host ""
Write-Host "Compte ASSISTANT :" -ForegroundColor Cyan
$ASSISTANT_NAME  = Read-Host "  Nom complet (ex: Sara Idrissi)"
$ASSISTANT_EMAIL = Read-Host "  Email (ex: sara@gmail.com)"
$ASSISTANT_PASS  = Read-Host "  Mot de passe (min. 8 caracteres)"
if ($ASSISTANT_NAME -eq "")  { $ASSISTANT_NAME  = "Assistant" }
if ($ASSISTANT_EMAIL -eq "") { $ASSISTANT_EMAIL = "assistant@cabinet.ma" }
if ($ASSISTANT_PASS -eq "")  { $ASSISTANT_PASS  = "ChangeMe2024!" }
Write-Host ""

$needRestart = $false

# ── 1. Verifier Laragon ────────────────────────────────────────
Step 1 8 "Verification de Laragon..."
foreach ($tool in @($PHP, $COMPOSER, $GIT, $MYSQL)) {
    if (-not (Test-Path $tool)) { ERR "Outil manquant : $tool`nInstallez Laragon depuis https://laragon.org/download/" }
}
OK "Laragon detecte"

# ── 2. Verifier licence ────────────────────────────────────────
Step 2 8 "Verification de la licence..."
$licFile = Join-Path $PSScriptRoot "dental-app.lic"
if (-not (Test-Path $licFile)) { ERR "dental-app.lic introuvable dans le meme dossier que INSTALLER.bat" }
OK "Licence trouvee"

# ── 3. Activer extension zip dans PHP ─────────────────────────
Step 3 8 "Configuration PHP..."
$phpIni = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini"
if (Test-Path $phpIni) {
    $ini = Get-Content $phpIni
    if ($ini -match ';extension=zip') {
        ($ini) -replace ';extension=zip', 'extension=zip' | Set-Content $phpIni
        OK "Extension zip activee"
    } else {
        OK "Extension zip deja active"
    }
}

# ── 4. Cloner le projet ────────────────────────────────────────
Step 4 8 "Telechargement de l'application..."
if (Test-Path $APP_DIR) {
    WARN "Le dossier $APP_DIR existe deja."
    $overwrite = Read-Host "  Ecraser et reinstaller ? (oui/non)"
    if ($overwrite -ne 'oui') { Write-Host "Installation annulee."; exit 0 }
    Remove-Item $APP_DIR -Recurse -Force
}
& $GIT clone "https://github.com/$GITHUB_REPO.git" $APP_DIR --branch $BRANCH --depth 1 2>&1 | Out-Null
if (-not (Test-Path "$APP_DIR\artisan")) { ERR "Clonage GitHub echoue. Verifiez connexion internet." }
& $GIT config --global --add safe.directory ($APP_DIR -replace '\\', '/')
Copy-Item $licFile "$APP_DIR\dental-app.lic" -Force
OK "Application telechargee"

# ── 5. Fichier .env ────────────────────────────────────────────
Step 5 8 "Configuration de l'environnement..."
$envContent = @"
APP_NAME=DentalApp
APP_ENV=production
APP_DEBUG=false
APP_URL=$APP_URL
APP_KEY=

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

DENTIST_NAME="$DENTIST_NAME"
DENTIST_EMAIL=$DENTIST_EMAIL
DENTIST_PASSWORD=$DENTIST_PASS

ASSISTANT_NAME="$ASSISTANT_NAME"
ASSISTANT_EMAIL=$ASSISTANT_EMAIL
ASSISTANT_PASSWORD=$ASSISTANT_PASS

FILESYSTEM_DISK=local
SESSION_DRIVER=cookie
SESSION_LIFETIME=480
CACHE_STORE=file
QUEUE_CONNECTION=database
BCRYPT_ROUNDS=12
"@
Set-Content -Path "$APP_DIR\.env" -Value $envContent -Encoding utf8
OK ".env cree"

# ── 6. Composer + cle Laravel ──────────────────────────────────
Step 6 8 "Installation des dependances PHP..."
$composerOut = cmd /c "`"$PHP`" `"$COMPOSER`" install --no-dev --optimize-autoloader --no-interaction --working-dir=`"$APP_DIR`"" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host ($composerOut -join "`n") -ForegroundColor Red
    ERR "composer install a echoue. Voir erreur ci-dessus."
}
& $PHP "$APP_DIR\artisan" key:generate --force 2>&1 | Out-Null
OK "Dependances PHP installees + cle generee"

# ── 7. Base de donnees ─────────────────────────────────────────
Step 7 8 "Base de donnees..."

# Verifier que MySQL est accessible
$ping = & $MYSQLADMIN -u root ping 2>$null
if ($ping -notmatch "alive") {
    ERR "MySQL n'est pas demarre. Ouvrez Laragon et cliquez 'Start All', puis relancez INSTALLER.bat."
}
Write-Host "  MySQL pret." -ForegroundColor Gray

# Creer la base de donnees
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName              = $MYSQL
$psi.Arguments             = "-u $DB_USER"
$psi.RedirectStandardInput = $true
$psi.RedirectStandardError = $true
$psi.UseShellExecute       = $false
$psi.CreateNoWindow        = $true
$proc = [System.Diagnostics.Process]::Start($psi)
$proc.StandardInput.WriteLine("CREATE DATABASE IF NOT EXISTS ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
$proc.StandardInput.Close()
$proc.WaitForExit()
if ($proc.ExitCode -ne 0) { ERR "Impossible de creer la base de donnees." }

& $PHP "$APP_DIR\artisan" migrate --force 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "Migrations echouees." }
& $PHP "$APP_DIR\artisan" db:seed --class=ProductionSeeder --force 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "Seeder echoue." }
& $PHP "$APP_DIR\artisan" storage:link --force 2>&1 | Out-Null
& $PHP "$APP_DIR\artisan" optimize 2>&1 | Out-Null
OK "Base de donnees initialisee"

# ── 8. Apache + Firewall + Taches ─────────────────────────────
Step 8 8 "Configuration reseau et demarrage..."

# VirtualHost 1 : hostname (dental-app-inch.test et dental.local)
$vh1 = "<VirtualHost *:80>`n    ServerName dental-app-inch.test`n    ServerAlias dental.local dental`n    DocumentRoot `"C:/laragon/www/dental-app-inch/public`"`n    <Directory `"C:/laragon/www/dental-app-inch/public`">`n        AllowOverride All`n        Require all granted`n    </Directory>`n</VirtualHost>"
Set-Content "C:\laragon\etc\apache2\sites-enabled\dental-app-inch.conf" -Value $vh1 -Encoding utf8

# VirtualHost 2 : catch-all pour acces par IP (tel, tablette, autre PC)
# Nomme 00- pour etre charge en premier et devenir le VirtualHost par defaut
$vh2 = "<VirtualHost *:80>`n    DocumentRoot `"C:/laragon/www/dental-app-inch/public`"`n    <Directory `"C:/laragon/www/dental-app-inch/public`">`n        AllowOverride All`n        Require all granted`n    </Directory>`n</VirtualHost>"
Set-Content "C:\laragon\etc\apache2\sites-enabled\00-dental-default.conf" -Value $vh2 -Encoding utf8

# Supprimer les anciens fichiers si existants
Remove-Item "C:\laragon\etc\apache2\sites-enabled\auto.dental-app-inch.test.conf" -Force -ErrorAction SilentlyContinue
Remove-Item "C:\laragon\etc\apache2\sites-enabled\dental-app-local.conf"          -Force -ErrorAction SilentlyContinue
Remove-Item "C:\laragon\etc\apache2\sites-enabled\dental-app-ip.conf"             -Force -ErrorAction SilentlyContinue

$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress
if (-not $localIP) { $localIP = "127.0.0.1" }

# Firewall
$fwRule = Get-NetFirewallRule -DisplayName "Dental App HTTP" -ErrorAction SilentlyContinue
if (-not $fwRule) {
    New-NetFirewallRule -DisplayName "Dental App HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow | Out-Null
}

# Redemarrer Apache avec le bon PATH (necessaire pour charger PHP)
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;$env:PATH"
Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
Start-Process -FilePath $HTTPD -WindowStyle Hidden
Start-Sleep -Seconds 2

# Taches planifiees (backup + scheduler)
if ($isAdmin) {
    powershell -ExecutionPolicy Bypass -File "$APP_DIR\scripts\schedule-tasks.ps1" | Out-Null
    OK "Taches automatiques planifiees"
}

# Renommer le PC
if ($isAdmin -and $env:COMPUTERNAME -ne "dental") {
    Rename-Computer -NewName "dental" -Force -ErrorAction SilentlyContinue
    OK "PC renomme en 'dental'"
    $needRestart = $true
}

# DentalApp.exe + raccourcis
powershell -ExecutionPolicy Bypass -File "$APP_DIR\scripts\build-exe.ps1" | Out-Null
OK "DentalApp.exe cree sur le Bureau"

OK "Apache demarre (dental-app-inch.test + http://$localIP)"

# ── Resume final ───────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "  INSTALLATION TERMINEE !" -ForegroundColor Green
Write-Host "=================================================="
Write-Host ""
Write-Host "  PC dentiste      : http://dental-app-inch.test"
Write-Host "  Autres appareils : http://$localIP"
Write-Host ""
Write-Host "  Dentiste  : $DENTIST_EMAIL  /  $DENTIST_PASS"
Write-Host "  Assistant : $ASSISTANT_EMAIL  /  $ASSISTANT_PASS"
Write-Host ""
Write-Host "  CHANGEZ LES MOTS DE PASSE APRES LA PREMIERE CONNEXION !" -ForegroundColor Yellow
Write-Host ""

if ($needRestart) {
    Write-Host "  Un redemarrage est necessaire pour activer dental.local" -ForegroundColor Yellow
    Write-Host ""
    $r = Read-Host "Redemarrer maintenant ? (oui/non)"
    if ($r -eq "oui") { Restart-Computer -Force }
} else {
    Write-Host "  Lancez DentalApp.exe sur le Bureau pour demarrer l'application."
    Write-Host ""
    Read-Host "Appuyez sur Entree pour terminer"
}
