# ═══════════════════════════════════════════════════════════════
#  install.ps1  —  Installation automatique  |  Dental App
#  Prérequis : Laragon Full installé dans C:\laragon
#  Usage     : Double-clic (PowerShell se lance automatiquement)
# ═══════════════════════════════════════════════════════════════

# ─── Configuration ─────────────────────────────────────────────
$GITHUB_REPO  = "hamidrherib/dental-app-inch"   # ton repo GitHub
$BRANCH       = "main"
$APP_DIR      = "C:\laragon\www\dental-app-inch"
$DB_NAME      = "dental_db_inch"
$DB_USER      = "root"
$DB_PASS      = ""                               # Laragon : root sans mot de passe par défaut
$APP_URL      = "http://dental-app-inch.test"
$VHOST_FILE   = "C:\laragon\etc\apache2\sites-enabled\auto.dental-app-inch.test.conf"

# ─── Chemins Laragon ───────────────────────────────────────────
$PHP      = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$COMPOSER = "C:\laragon\bin\composer\composer.phar"
$GIT      = "C:\laragon\bin\git\bin\git.exe"
$MYSQL    = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$NODE     = "C:\laragon\bin\nodejs\node-v22\node.exe"
$NPM      = "C:\laragon\bin\nodejs\node-v22\npm.cmd"

# ═══════════════════════════════════════════════════════════════
function Step($n, $total, $msg) {
    Write-Host ""
    Write-Host "[$n/$total] $msg" -ForegroundColor Cyan
}
function OK($msg)    { Write-Host "  OK  $msg" -ForegroundColor Green }
function ERR($msg)   { Write-Host "  [ERREUR] $msg" -ForegroundColor Red; Read-Host "Appuyez sur Entree pour quitter"; exit 1 }
function WARN($msg)  { Write-Host "  ATTENTION  $msg" -ForegroundColor Yellow }
function INFO($msg)  { Write-Host "  $msg" }

# ═══════════════════════════════════════════════════════════════
Clear-Host
Write-Host "=================================================="
Write-Host "   Installation Dental App"
Write-Host "=================================================="
Write-Host ""

# ─── Vérification Laragon Full ─────────────────────────────────
Step 1 10 "Vérification de Laragon..."

$required = @($PHP, $COMPOSER, $GIT, $MYSQL, $NODE, $NPM)
foreach ($tool in $required) {
    if (-not (Test-Path $tool)) {
        ERR "Outil manquant : $tool`n`n  Installez Laragon Full depuis https://laragon.org/download/`n  Choisissez la version 'Full' (pas Lite)"
    }
}
OK "Laragon Full détecté"

# ─── Vérification licence ──────────────────────────────────────
Step 2 10 "Vérification de la licence..."

$licFile = Join-Path $PSScriptRoot "..\dental-app.lic"
if (-not (Test-Path $licFile)) {
    ERR "Fichier dental-app.lic introuvable.`n`n  Ce fichier doit être fourni avec le script d'installation.`n  Contactez votre fournisseur."
}
OK "Fichier de licence trouvé"

# ─── Cloner le projet ──────────────────────────────────────────
Step 3 10 "Téléchargement de l'application..."

if (Test-Path $APP_DIR) {
    WARN "Le dossier $APP_DIR existe déjà."
    $overwrite = Read-Host "  Écraser ? (oui/non)"
    if ($overwrite -ne 'oui') { Write-Host "Installation annulée."; exit 0 }
    Remove-Item $APP_DIR -Recurse -Force
}

& $GIT clone "https://github.com/$GITHUB_REPO.git" $APP_DIR --branch $BRANCH --depth 1 2>&1 | Out-Null
if (-not (Test-Path "$APP_DIR\artisan")) { ERR "Clonage GitHub échoué. Vérifiez votre connexion internet." }
OK "Application téléchargée"

# ─── Copier la licence ─────────────────────────────────────────
Copy-Item $licFile "$APP_DIR\dental-app.lic" -Force
OK "Licence installée"

# ─── Fichier .env ──────────────────────────────────────────────
Step 4 10 "Configuration de l'environnement..."

$envContent = @"
APP_NAME="Dental App"
APP_ENV=production
APP_DEBUG=false
APP_URL=$APP_URL

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

FILESYSTEM_DISK=local
"@

Set-Content -Path "$APP_DIR\.env" -Value $envContent -Encoding utf8
OK ".env créé"

# ─── Composer install ──────────────────────────────────────────
Step 5 10 "Installation des dépendances PHP..."

$composerCmd = "`"$PHP`" `"$COMPOSER`" install --no-dev --optimize-autoloader --no-interaction --working-dir=`"$APP_DIR`""
cmd /c $composerCmd 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "composer install a échoué." }
OK "Dépendances PHP installées"

# ─── Clé Laravel ───────────────────────────────────────────────
Step 6 10 "Génération de la clé Laravel..."

& $PHP "$APP_DIR\artisan" key:generate --force 2>&1 | Out-Null
OK "Clé Laravel générée"

# ─── Base de données ───────────────────────────────────────────
Step 7 10 "Création de la base de données..."

$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName               = $MYSQL
$psi.Arguments              = "-u $DB_USER"
if ($DB_PASS) { $psi.Arguments += " -p$DB_PASS" }
$psi.RedirectStandardInput  = $true
$psi.RedirectStandardError  = $true
$psi.UseShellExecute        = $false
$psi.CreateNoWindow         = $true

$proc = [System.Diagnostics.Process]::Start($psi)
$proc.StandardInput.WriteLine("CREATE DATABASE IF NOT EXISTS ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
$proc.StandardInput.Close()
$proc.WaitForExit()

if ($proc.ExitCode -ne 0) { ERR "Création de la base de données échouée. MySQL est-il démarré dans Laragon ?" }
OK "Base de données créée"

# ─── Migrations + Seed ─────────────────────────────────────────
& $PHP "$APP_DIR\artisan" migrate --force 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "Migrations échouées." }

& $PHP "$APP_DIR\artisan" db:seed --class=DemoDataSeeder --force 2>&1 | Out-Null
OK "Base de données initialisée"

# ─── Storage link ──────────────────────────────────────────────
& $PHP "$APP_DIR\artisan" storage:link --force 2>&1 | Out-Null
OK "Lien storage créé"

# ─── npm install + build ───────────────────────────────────────
Step 8 10 "Build du frontend..."

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
cmd /c "`"$NPM`" install --prefix `"$APP_DIR`" 2>&1" | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "npm install a échoué." }

cmd /c "`"$NPM`" run build --prefix `"$APP_DIR`" 2>&1" | Out-Null
if ($LASTEXITCODE -ne 0) { ERR "npm run build a échoué." }
OK "Frontend compilé"

# ─── VirtualHost Apache ────────────────────────────────────────
Step 9 10 "Configuration Apache..."

$vhostContent = @"
<VirtualHost *:80>
    ServerName dental-app-inch.test
    DocumentRoot "C:/laragon/www/dental-app-inch/public"
    <Directory "C:/laragon/www/dental-app-inch/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@

Set-Content -Path $VHOST_FILE -Value $vhostContent -Encoding utf8
OK "VirtualHost Apache configuré"

# ─── Backup automatique ────────────────────────────────────────
Step 10 10 "Planification des sauvegardes automatiques..."

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    & "$APP_DIR\scripts\schedule-tasks.ps1" | Out-Null
    OK "Sauvegardes planifiées (Lun-Ven 18h, Sam 12h30)"
} else {
    WARN "Sauvegardes non planifiées (relancez install.ps1 en Administrateur pour activer)"
}

# ─── Raccourcis Bureau ─────────────────────────────────────────
Copy-Item "$APP_DIR\scripts\DEMARRER.bat"  "$env:USERPROFILE\Desktop\DEMARRER.bat"  -Force
Copy-Item "$APP_DIR\scripts\FERMER.bat"    "$env:USERPROFILE\Desktop\FERMER.bat"    -Force
Copy-Item "$APP_DIR\scripts\BACKUP.bat"    "$env:USERPROFILE\Desktop\BACKUP.bat"    -Force
Copy-Item "$APP_DIR\scripts\RESTAURER.bat" "$env:USERPROFILE\Desktop\RESTAURER.bat" -Force
OK "Raccourcis créés sur le Bureau"

# ─── Résumé final ──────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "  INSTALLATION TERMINEE !" -ForegroundColor Green
Write-Host "=================================================="
Write-Host ""
Write-Host "  1. Ouvrez Laragon et demarrez Apache + MySQL"
Write-Host "  2. Ouvrez votre navigateur sur : $APP_URL"
Write-Host ""
Write-Host "  Comptes par defaut :"
Write-Host "    Dentiste  : dentiste@demo.com  /  password"
Write-Host "    Assistant : assistant@demo.com /  password"
Write-Host ""
Write-Host "  PENSEZ A CHANGER LES MOTS DE PASSE !" -ForegroundColor Yellow
Write-Host ""
Read-Host "Appuyez sur Entree pour terminer"
