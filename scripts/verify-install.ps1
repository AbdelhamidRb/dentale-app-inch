# ═══════════════════════════════════════════════════════════════
#  verify-install.ps1  -  Vérification complète de l'installation
#  Dental App  |  Clic droit -> Exécuter en tant qu'administrateur
# ═══════════════════════════════════════════════════════════════

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
$OK       = 0
$WARN     = 0
$FAIL     = 0

function Pass($msg)  { Write-Host "  [OK]   $msg" -ForegroundColor Green;  $script:OK++   }
function Warn($msg)  { Write-Host "  [WARN] $msg" -ForegroundColor Yellow; $script:WARN++ }
function Fail($msg)  { Write-Host "  [FAIL] $msg" -ForegroundColor Red;    $script:FAIL++ }
function Title($msg) { Write-Host ""; Write-Host "── $msg" -ForegroundColor Cyan }

Clear-Host
Write-Host "=================================================="
Write-Host "   Verification installation Dental App"
Write-Host "   $(Get-Date -Format 'dd/MM/yyyy HH:mm')"
Write-Host "=================================================="


# ── 1. DOSSIER APPLICATION ─────────────────────────────────────
Title "1. Dossier application"

if (Test-Path "$APP_ROOT\artisan")         { Pass "Dossier app present : $APP_ROOT" }
else                                       { Fail "Dossier app INTROUVABLE : $APP_ROOT" }

if (Test-Path "$APP_ROOT\.env")            { Pass ".env present" }
else                                       { Fail ".env MANQUANT - app non configuree" }

if (Test-Path "$APP_ROOT\dental-app.lic")  { Pass "Licence dental-app.lic presente" }
else                                       { Fail "Licence MANQUANTE - app bloquee" }

if (Test-Path "$APP_ROOT\storage\app\license.pub") { Pass "license.pub present" }
else                                       { Fail "license.pub MANQUANT - API bloquee" }

if (Test-Path "$APP_ROOT\public\storage")  { Pass "Lien storage/public present" }
else                                       { Warn "Lien storage manquant - images patients invisibles (lancer: php artisan storage:link)" }

if (Test-Path "$APP_ROOT\vendor\autoload.php") { Pass "Vendor (Composer) present" }
else                                       { Fail "Vendor MANQUANT - lancer: composer install" }

if (Test-Path "$APP_ROOT\public\build\manifest.json") { Pass "Build Vite present" }
else                                       { Fail "Build manquant - lancer: npm run build" }


# ── 2. SERVICES LARAGON ────────────────────────────────────────
Title "2. Services Apache + MySQL"

$apache = Get-Process "httpd" -ErrorAction SilentlyContinue
if ($apache)  { Pass "Apache en cours d'execution (PID: $($apache.Id -join ', '))" }
else           { Fail "Apache ARRETE - lancer Laragon et cliquer Start All" }

$mysql = Get-Process "mysqld" -ErrorAction SilentlyContinue
if ($mysql)   { Pass "MySQL en cours d'execution (PID: $($mysql.Id -join ', '))" }
else           { Fail "MySQL ARRETE - lancer Laragon et cliquer Start All" }


# ── 3. PHP & OPCACHE ──────────────────────────────────────────
Title "3. PHP et OPcache"

$phpDir = Get-ChildItem "$LARAGON_ROOT\bin\php" -Directory -ErrorAction SilentlyContinue |
          Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1

if ($phpDir) {
    Pass "PHP detecte : $($phpDir.Name)"
    $PHP = Join-Path $phpDir.FullName "php.exe"

    $vts = & $PHP -r "echo ini_get('opcache.validate_timestamps');" 2>$null
    if ($vts -eq "1") { Pass "OPcache validate_timestamps = 1 (correct)" }
    else               { Warn "OPcache validate_timestamps = $vts (devrait etre 1)" }

    $rf = & $PHP -r "echo ini_get('opcache.revalidate_freq');" 2>$null
    if ([int]$rf -le 5) { Pass "OPcache revalidate_freq = $rf secondes" }
    else                 { Warn "OPcache revalidate_freq = $rf (devrait etre 2)" }

    $zip = & $PHP -r "echo extension_loaded('zip') ? 'ok' : 'manquant';" 2>$null
    if ($zip -eq "ok") { Pass "Extension PHP zip activee" }
    else                { Fail "Extension PHP zip MANQUANTE - backup images impossible" }

} else { Fail "PHP 8.x introuvable dans $LARAGON_ROOT\bin\php" }


# ── 4. BASE DE DONNÉES ─────────────────────────────────────────
Title "4. Base de donnees"

$mysqlDir = Get-ChildItem "$LARAGON_ROOT\bin\mysql" -Directory -ErrorAction SilentlyContinue |
            Sort-Object Name -Descending | Select-Object -First 1

if ($mysqlDir) {
    $mysqladmin = Join-Path $mysqlDir.FullName "bin\mysqladmin.exe"
    $ping = & $mysqladmin -u root ping 2>$null
    if ($ping -match "alive") { Pass "MySQL accessible (root sans mot de passe)" }
    else {
        # Essayer avec mot de passe depuis .env
        $envPass = ""
        if (Test-Path "$APP_ROOT\.env") {
            $line = Get-Content "$APP_ROOT\.env" | Where-Object { $_ -match "^DB_PASSWORD=" } | Select-Object -First 1
            if ($line) { $envPass = ($line -split '=', 2)[1].Trim('"').Trim("'") }
        }
        if ($envPass) {
            $ping2 = & $mysqladmin -u root "-p$envPass" ping 2>$null
            if ($ping2 -match "alive") { Pass "MySQL accessible (avec mot de passe .env)" }
            else { Fail "MySQL NON accessible - verifier credentials dans .env" }
        } else { Fail "MySQL NON accessible" }
    }

    # Vérifier que la base existe (avec mot de passe .env si present)
    $mysql = Join-Path $mysqlDir.FullName "bin\mysql.exe"
    $passArg = if ($envPass) { "-p$envPass" } else { "" }
    if ($passArg) {
        $dbCheck = echo "SHOW DATABASES LIKE 'dental_db_inch';" | & $mysql -u root $passArg 2>$null
    } else {
        $dbCheck = echo "SHOW DATABASES LIKE 'dental_db_inch';" | & $mysql -u root 2>$null
    }
    if ($dbCheck -match "dental_db_inch") { Pass "Base de donnees 'dental_db_inch' presente" }
    else                                   { Fail "Base de donnees 'dental_db_inch' MANQUANTE" }
} else { Fail "MySQL introuvable dans $LARAGON_ROOT\bin\mysql" }


# ── 5. MIGRATIONS LARAVEL ──────────────────────────────────────
Title "5. Migrations Laravel"

if ($phpDir) {
    $migrate = & $PHP "$APP_ROOT\artisan" migrate:status --no-ansi 2>$null | Out-String
    $pending = ($migrate -split "`n" | Where-Object { $_ -match "Pending" }).Count
    $ran     = ($migrate -split "`n" | Where-Object { $_ -match "Ran" }).Count

    if ($pending -eq 0) { Pass "Toutes les migrations executees ($ran migrations)" }
    else                 { Warn "$pending migration(s) en attente - lancer: php artisan migrate --force" }
}


# ── 6. TACHES PLANIFIEES ──────────────────────────────────────
Title "6. Taches planifiees Windows"

$tasksRequired = @("DentalApp-Backup", "DentalApp-USBSync", "DentalApp-Scheduler")

foreach ($name in $tasksRequired) {
    $t = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
    if ($t) {
        $info = Get-ScheduledTaskInfo -TaskName $name -ErrorAction SilentlyContinue
        $lastCode = $info.LastTaskResult
        $nextRun  = $info.NextRunTime

        if ($t.State -eq "Ready" -or $t.State -eq "Running") {
            if ($lastCode -eq 0 -or $lastCode -eq 267011) {
                Pass "$name - Etat: $($t.State) | Prochaine: $nextRun"
            } else {
                Warn "$name - Etat: $($t.State) | Derniere erreur code: $lastCode"
            }
        } else {
            Warn "$name - Etat inattendu: $($t.State)"
        }
    } else {
        if ($name -eq "DentalApp-Scheduler") {
            Fail "$name MANQUANTE - lancer fix-scheduler.bat en admin"
        } else {
            Fail "$name MANQUANTE - relancer schedule-tasks.ps1 en admin"
        }
    }
}

# Vérifier le dernier backup
Title "7. Dernier backup"

$backupDir = "$BACKUP_DIR"
if (Test-Path $backupDir) {
    $lastBackup = Get-ChildItem $backupDir -Directory | Sort-Object Name -Descending | Select-Object -First 1
    if ($lastBackup) {
        $sqlFile = Join-Path $lastBackup.FullName "database.sql"
        $zipFile = Join-Path $lastBackup.FullName "images.zip"
        $age     = (Get-Date) - $lastBackup.CreationTime
        $ageDays = [math]::Round($age.TotalDays, 1)

        if ($ageDays -le 2)  { Pass "Dernier backup : $($lastBackup.Name) (il y a $ageDays jour(s))" }
        elseif ($ageDays -le 7) { Warn "Dernier backup : $($lastBackup.Name) (il y a $ageDays jours - ancien)" }
        else  { Fail "Dernier backup : $($lastBackup.Name) (il y a $ageDays jours - TRES ANCIEN)" }

        if (Test-Path $sqlFile) { Pass "database.sql present ($([math]::Round((Get-Item $sqlFile).Length/1KB)) Ko)" }
        else                     { Fail "database.sql MANQUANT dans le backup" }

        if (Test-Path $zipFile) { Pass "images.zip present ($([math]::Round((Get-Item $zipFile).Length/1MB, 1)) MB)" }
        else                     { Warn "images.zip absent (normal si aucune image patient)" }

    } else { Warn "Dossier backup vide - aucun backup effectue" }
} else { Warn "Dossier $BACKUP_DIR inexistant - premier backup pas encore fait" }


# ── 8. APPLICATION WEB ─────────────────────────────────────────
Title "8. Application web"

try {
    $resp = Invoke-WebRequest -Uri "http://dental-app-inch.test/api/up" -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
    if ($resp.StatusCode -eq 200) { Pass "App accessible sur http://dental-app-inch.test" }
    else                           { Warn "App repond mais status: $($resp.StatusCode)" }
} catch {
    try {
        $resp2 = Invoke-WebRequest -Uri "http://127.0.0.1/api/up" -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
        if ($resp2.StatusCode -eq 200) { Pass "App accessible sur http://127.0.0.1" }
        else { Fail "App inaccessible - verifier Apache" }
    } catch { Fail "App inaccessible - Apache arrete ou VirtualHost mal configure" }
}

# Vérifier IP locale
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress
if ($localIP) { Pass "IP locale du cabinet : http://$localIP (accessible depuis autres appareils)" }
else           { Warn "IP locale non detectee - assistants ne pourront pas se connecter" }


# ── 9. LOGS ERREURS ────────────────────────────────────────────
Title "9. Logs d'erreurs recents"

$logFile = "$APP_ROOT\storage\logs\laravel.log"
if (Test-Path $logFile) {
    $errors = Get-Content $logFile -Tail 100 | Where-Object { $_ -match "\[ERROR\]|\[CRITICAL\]" }
    if ($errors.Count -eq 0) { Pass "Aucune erreur critique dans les logs" }
    else {
        Warn "$($errors.Count) erreur(s) dans les logs recents :"
        $errors | Select-Object -Last 3 | ForEach-Object { Write-Host "    $_" -ForegroundColor DarkYellow }
    }
} else { Warn "Fichier log absent (normal si app jamais lancee)" }


# ── RÉSUMÉ FINAL ───────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "   RÉSUMÉ"
Write-Host "=================================================="
Write-Host "  OK   : $OK  verification(s) passee(s)" -ForegroundColor Green
if ($WARN -gt 0) { Write-Host "  WARN : $WARN  avertissement(s)"  -ForegroundColor Yellow }
if ($FAIL -gt 0) { Write-Host "  FAIL : $FAIL  probleme(s) critique(s)" -ForegroundColor Red }
Write-Host ""

if ($FAIL -eq 0 -and $WARN -eq 0) {
    Write-Host "  Installation parfaite !" -ForegroundColor Green
} elseif ($FAIL -eq 0) {
    Write-Host "  Installation OK avec avertissements mineurs." -ForegroundColor Yellow
} else {
    Write-Host "  Des problemes critiques necessitent une action." -ForegroundColor Red
}
Write-Host ""
if ([Environment]::UserInteractive) { Read-Host "Appuyez sur Entree pour fermer" }
