# ═══════════════════════════════════════════════════════════════
#  update.ps1  -  Mise a jour de l'application  |  Dental App
#  1. Backup automatique
#  2. git pull depuis GitHub
#  3. composer install (si besoin)
#  4. php artisan migrate
#  5. php artisan optimize
#  6. Redemarrage Apache
# ═══════════════════════════════════════════════════════════════

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
$BRANCH   = "main"

# Trouver PHP
$phpDir = Get-ChildItem "$LARAGON_ROOT\bin\php" -Directory -ErrorAction SilentlyContinue |
          Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1
if (-not $phpDir) { Write-Host "[ERREUR] PHP 8.x introuvable" -ForegroundColor Red; exit 1 }
$PHP = Join-Path $phpDir.FullName "php.exe"

# Trouver Composer
$COMPOSER = "$LARAGON_ROOT\bin\composer\composer.phar"

# Ajouter Git au PATH
$env:PATH = "$LARAGON_ROOT\bin\git\bin;$LARAGON_ROOT\bin\git\usr\bin;$env:PATH"

function Step($n, $t, $msg) {
    Write-Host ""
    Write-Host "[$n/$t] $msg" -ForegroundColor Cyan
}
function OK($msg)  { Write-Host "  OK   $msg" -ForegroundColor Green }
function ERR($msg) { Write-Host "  FAIL $msg" -ForegroundColor Red; Read-Host "Entree pour quitter"; exit 1 }

Clear-Host
Write-Host ""
Write-Host "=================================================="
Write-Host "   MISE A JOUR - Dental App"
Write-Host "   $(Get-Date -Format 'dd/MM/yyyy HH:mm')"
Write-Host "=================================================="

# ── 1. Backup avant mise a jour ────────────────────────────────
Step 1 6 "Sauvegarde avant mise a jour..."
& "$APP_ROOT\scripts\backup.ps1"
if ($LASTEXITCODE -ne 0) { ERR "Backup echoue - mise a jour annulee pour proteger vos donnees." }

# ── 2. Git pull ────────────────────────────────────────────────
Step 2 6 "Telechargement de la mise a jour..."
Set-Location $APP_ROOT

# Verifier s'il y a des mises a jour disponibles
git fetch origin $BRANCH 2>&1 | Out-Null
$behind = git rev-list HEAD..origin/$BRANCH --count 2>$null
if ($behind -eq "0") {
    Write-Host "  INFO Application deja a jour - aucune mise a jour disponible." -ForegroundColor Yellow
    Read-Host "Entree pour fermer"
    exit 0
}

Write-Host "  $behind nouveau(x) commit(s) disponible(s)..." -ForegroundColor Gray
git pull origin $BRANCH 2>&1
if ($LASTEXITCODE -ne 0) { ERR "git pull echoue - verifiez votre connexion internet." }
OK "Code mis a jour depuis GitHub ($BRANCH)"

# Detecter ce qui a change
$changedFiles = git diff HEAD~1 HEAD --name-only 2>$null
$composerChanged  = $changedFiles -match "composer\.(json|lock)"
$migrationChanged = $changedFiles -match "database/migrations"

# ── 3. Composer (si besoin) ───────────────────────────────────
Step 3 6 "Dependances PHP..."
if ($composerChanged) {
    Write-Host "  composer.json modifie - mise a jour des packages..." -ForegroundColor Gray
    & cmd /c "`"$PHP`" `"$COMPOSER`" install --no-dev --optimize-autoloader --no-interaction --working-dir=`"$APP_ROOT`""
    if ($LASTEXITCODE -ne 0) { ERR "composer install echoue." }
    OK "Packages PHP mis a jour"
} else {
    OK "Packages PHP inchanges (ignore)"
}

# ── 4. Migrations ─────────────────────────────────────────────
Step 4 6 "Base de donnees..."
if ($migrationChanged) {
    & $PHP "$APP_ROOT\artisan" migrate --force
    if ($LASTEXITCODE -ne 0) { ERR "Migrations echouees." }
    OK "Migrations executees"
} else {
    OK "Aucune nouvelle migration (ignore)"
}

# ── 5. Optimisation Laravel ───────────────────────────────────
Step 5 6 "Optimisation Laravel..."
& $PHP "$APP_ROOT\artisan" optimize 2>&1 | Out-Null
& $PHP "$APP_ROOT\artisan" cache:clear 2>&1 | Out-Null
OK "Caches Laravel vides et reconstruits"

# ── 6. Redemarrage Apache ─────────────────────────────────────
Step 6 6 "Redemarrage Apache..."
$httpdPath = Get-ChildItem "$LARAGON_ROOT\bin\apache" -Recurse -Filter "httpd.exe" -ErrorAction SilentlyContinue |
             Select-Object -First 1 -ExpandProperty FullName
if ($httpdPath) {
    $env:PATH = "$($phpDir.FullName);$env:PATH"
    Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    Start-Process -FilePath $httpdPath -WindowStyle Hidden
    Start-Sleep -Seconds 2
    $running = Get-Process httpd -ErrorAction SilentlyContinue
    if ($running) { OK "Apache redemarre (OPcache rechargee)" }
    else           { Write-Host "  WARN Apache n'a pas redemarre - ouvrez Laragon et cliquez Start All" -ForegroundColor Yellow }
} else {
    Write-Host "  WARN httpd.exe introuvable - redemarrez Apache manuellement" -ForegroundColor Yellow
}

# ── Nettoyage taches planifiees obsoletes ─────────────────────
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (Get-ScheduledTask -TaskName "DentalApp-Scheduler" -ErrorAction SilentlyContinue) {
    if ($isAdmin) {
        Unregister-ScheduledTask -TaskName "DentalApp-Scheduler" -Confirm:$false -ErrorAction SilentlyContinue
    } else {
        Start-Process powershell -Verb RunAs -ArgumentList "-Command `"Unregister-ScheduledTask -TaskName 'DentalApp-Scheduler' -Confirm:`$false -ErrorAction SilentlyContinue`"" -Wait -WindowStyle Hidden -ErrorAction SilentlyContinue
    }
    OK "Tache DentalApp-Scheduler supprimee"
}

# ── Resume ────────────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "   MISE A JOUR TERMINEE !" -ForegroundColor Green
Write-Host "=================================================="
Write-Host ""
Write-Host "  Rechargez la page dans le navigateur (Ctrl+Shift+R)"
Write-Host ""
Read-Host "Entree pour fermer"
