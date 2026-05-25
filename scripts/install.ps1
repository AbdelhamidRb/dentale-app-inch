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

$PHP      = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$COMPOSER = "C:\laragon\bin\composer\composer.phar"
$GIT      = "C:\laragon\bin\git\bin\git.exe"
$MYSQL    = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

# Ajouter Git au PATH pour que Composer puisse l'utiliser
$env:Path = "C:\laragon\bin\git\bin;C:\laragon\bin\git\usr\bin;$env:Path"

function Step($n, $t, $msg) { Write-Host ""; Write-Host "[$n/$t] $msg" -ForegroundColor Cyan }
function OK($msg)   { Write-Host "  OK  $msg" -ForegroundColor Green }
function ERR($msg)  { Write-Host "  [ERREUR] $msg" -ForegroundColor Red; Read-Host "Appuyez sur Entree pour quitter"; exit 1 }
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
$composerCmd = "`"$PHP`" `"$COMPOSER`" install --no-dev --optimize-autoloader --no-interaction --working-dir=`"$APP_DIR`""
cmd /c $composerCmd 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "composer install a echoue." }
& $PHP "$APP_DIR\artisan" key:generate --force 2>&1 | Out-Null
OK "Dependances PHP installees + cle generee"

# ── 7. Base de donnees ─────────────────────────────────────────
Step 7 8 "Creation de la base de donnees..."
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
if ($proc.ExitCode -ne 0) { ERR "Impossible de creer la base. MySQL est-il demarre dans Laragon ?" }

& $PHP "$APP_DIR\artisan" migrate --force 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "Migrations echouees." }
& $PHP "$APP_DIR\artisan" db:seed --class=DemoDataSeeder --force 2>&1 | Out-Null
& $PHP "$APP_DIR\artisan" storage:link --force 2>&1 | Out-Null
& $PHP "$APP_DIR\artisan" optimize 2>&1 | Out-Null
OK "Base de donnees initialisee"

# ── 8. Apache + Firewall + IP ──────────────────────────────────
Step 8 8 "Configuration reseau..."

$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress
if (-not $localIP) { $localIP = "127.0.0.1" }

$vh1 = "<VirtualHost *:80>`n    ServerName dental-app-inch.test`n    DocumentRoot `"C:/laragon/www/dental-app-inch/public`"`n    <Directory `"C:/laragon/www/dental-app-inch/public`">`n        AllowOverride All`n        Require all granted`n    </Directory>`n</VirtualHost>"
Set-Content "C:\laragon\etc\apache2\sites-enabled\auto.dental-app-inch.test.conf" -Value $vh1 -Encoding utf8

$vh2 = "<VirtualHost *:80>`n    ServerName dental.local`n    DocumentRoot `"C:/laragon/www/dental-app-inch/public`"`n    <Directory `"C:/laragon/www/dental-app-inch/public`">`n        AllowOverride All`n        Require all granted`n    </Directory>`n</VirtualHost>"
Set-Content "C:\laragon\etc\apache2\sites-enabled\dental-app-local.conf" -Value $vh2 -Encoding utf8

$vh3 = "<VirtualHost ${localIP}:80>`n    ServerName $localIP`n    DocumentRoot `"C:/laragon/www/dental-app-inch/public`"`n    <Directory `"C:/laragon/www/dental-app-inch/public`">`n        AllowOverride All`n        Require all granted`n    </Directory>`n</VirtualHost>"
Set-Content "C:\laragon\etc\apache2\sites-enabled\dental-app-ip.conf" -Value $vh3 -Encoding utf8

$fwRule = Get-NetFirewallRule -DisplayName "Dental App HTTP" -ErrorAction SilentlyContinue
if (-not $fwRule) {
    New-NetFirewallRule -DisplayName "Dental App HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow | Out-Null
    OK "Firewall port 80 ouvert"
}

if ($isAdmin -and $env:COMPUTERNAME -ne "dental") {
    Rename-Computer -NewName "dental" -Force -ErrorAction SilentlyContinue
    OK "PC renomme en 'dental' (dental.local actif apres redemarrage)"
    $needRestart = $true
}

if ($isAdmin) {
    & "$APP_DIR\scripts\schedule-tasks.ps1" | Out-Null
    OK "Sauvegardes automatiques planifiees"
}

OK "Apache configure (dental-app-inch.test + http://$localIP)"

# ── DentalApp.exe + raccourcis Bureau ─────────────────────────
powershell -ExecutionPolicy Bypass -File "$APP_DIR\scripts\build-exe.ps1"
OK "DentalApp.exe et raccourcis crees sur le Bureau"

# ── Resume final ───────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "  INSTALLATION TERMINEE !" -ForegroundColor Green
Write-Host "=================================================="
Write-Host ""
Write-Host "  PC dentiste      : http://dental-app-inch.test"
Write-Host "  Autres appareils : http://$localIP"
Write-Host ""
Write-Host "  Comptes par defaut :"
Write-Host "    Dentiste  : dentiste@demo.com  / password"
Write-Host "    Assistant : assistant@demo.com / password"
Write-Host ""
Write-Host "  PENSEZ A CHANGER LES MOTS DE PASSE !" -ForegroundColor Yellow
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
