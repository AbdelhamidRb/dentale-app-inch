# ═══════════════════════════════════════════════════════════════
#  backup.ps1  —  Sauvegarde automatique  |  Dental App
#  Destinations : local (adaptatif) + OneDrive (dynamique) + USB (sync auto)
# ═══════════════════════════════════════════════════════════════

$DB_NAME    = "dental_db_inch"
$DB_USER    = "root"
$DB_PASS    = "hamid2003"
$APP_ROOT   = "C:\laragon\www\dental-app-inch"
$BACKUP_DIR = "C:\backups\dental-app"
$USB_LABEL  = "DENTAL-BKP"
$MYSQL_BIN  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin"
$mysqldump  = "$MYSQL_BIN\mysqldump.exe"

# ─── Nombre de backups à conserver selon la taille ────────────
function Get-MaxBackups([long]$sizeBytes) {
    $sizeMB = $sizeBytes / 1MB
    if ($sizeMB -lt 50)  { return 30 }
    if ($sizeMB -lt 200) { return 20 }
    if ($sizeMB -lt 500) { return 10 }
    return 5
}

# ─── Vérifier mysqldump ────────────────────────────────────────
if (-not (Test-Path $mysqldump)) {
    Write-Host "[ERREUR] mysqldump.exe introuvable : $mysqldump" -ForegroundColor Red
    exit 1
}

# ─── Bannière ──────────────────────────────────────────────────
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm"
Write-Host ""
Write-Host "=================================================="
Write-Host "  BACKUP Dental App -- $timestamp"
Write-Host "=================================================="
Write-Host ""

# ─── Créer le dossier de backup ────────────────────────────────
New-Item -ItemType Directory -Force -Path $BACKUP_DIR | Out-Null
$dest = Join-Path $BACKUP_DIR $timestamp
New-Item -ItemType Directory -Force -Path $dest | Out-Null

# ─── [1/4] Export BDD ──────────────────────────────────────────
Write-Host "[1/4] Sauvegarde de la base de donnees..."
$sqlFile = Join-Path $dest "database.sql"

$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName               = $mysqldump
$psi.Arguments              = "-u $DB_USER -p$DB_PASS --single-transaction --default-character-set=utf8mb4 --routines $DB_NAME"
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError  = $true
$psi.UseShellExecute        = $false
$psi.CreateNoWindow         = $true

$proc   = [System.Diagnostics.Process]::Start($psi)
$output = $proc.StandardOutput.ReadToEnd()
$errors = $proc.StandardError.ReadToEnd()
$proc.WaitForExit()

[System.IO.File]::WriteAllText($sqlFile, $output, [System.Text.Encoding]::UTF8)

if ($proc.ExitCode -ne 0 -or $output.Length -lt 500) {
    Write-Host "  [ERREUR] Export MySQL echoue." -ForegroundColor Red
    if ($errors) { Write-Host "  $errors" -ForegroundColor Red }
    Remove-Item $dest -Recurse -Force
    exit 1
}
$sizeMB = [math]::Round((Get-Item $sqlFile).Length / 1MB, 2)
Write-Host "  OK  database.sql  ($sizeMB MB)" -ForegroundColor Green

# ─── [2/4] Compresser les images ───────────────────────────────
Write-Host "[2/4] Compression des images patients..."
$imagesPath = Join-Path $APP_ROOT "storage\app\public\patients"
$zipFile    = Join-Path $dest "images.zip"

if (Test-Path $imagesPath) {
    Compress-Archive -Path $imagesPath -DestinationPath $zipFile -Force
    $sizeIMG = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)
    Write-Host "  OK  images.zip  ($sizeIMG MB)" -ForegroundColor Green
} else {
    Write-Host "  INFO  Aucune image trouvee (ignore)"
}

# ─── [3/4] Copie vers OneDrive (dynamique) ─────────────────────
Write-Host "[3/4] Copie vers OneDrive..."

$onedrivePath = $env:OneDrive
if (-not $onedrivePath -or -not (Test-Path $onedrivePath)) {
    $onedrivePath = "$env:USERPROFILE\OneDrive"
}
if (-not (Test-Path $onedrivePath)) {
    $onedrivePath = $null
}

if ($onedrivePath) {
    $odDir = Join-Path $onedrivePath "DentalApp-Backups"
    New-Item -ItemType Directory -Force -Path $odDir | Out-Null

    # Calculer taille du backup actuel
    $backupSizeBytes = (Get-ChildItem $dest -Recurse | Measure-Object -Property Length -Sum).Sum
    if (-not $backupSizeBytes -or $backupSizeBytes -eq 0) { $backupSizeBytes = 10MB }

    # Calculer combien de backups rentrent dans 1 Go
    $ONEDRIVE_QUOTA = 1GB
    $maxOD = [math]::Floor($ONEDRIVE_QUOTA / $backupSizeBytes)
    $maxOD = [math]::Max(5, [math]::Min(60, $maxOD))

    # Copier le backup actuel
    $odDest = Join-Path $odDir $timestamp
    Copy-Item $dest $odDest -Recurse -Force

    # Supprimer les anciens (garder $maxOD)
    $oldOD = Get-ChildItem $odDir -Directory | Sort-Object Name | Select-Object -SkipLast $maxOD
    foreach ($b in $oldOD) { Remove-Item $b.FullName -Recurse -Force }

    $sizeTotalMB = [math]::Round($backupSizeBytes / 1MB, 1)
    Write-Host "  OK  OneDrive : $odDest  ($sizeTotalMB MB, max $maxOD backups gardes)" -ForegroundColor Green
} else {
    Write-Host "  ATTENTION  OneDrive non trouve. Installez Google Drive for Desktop ou connectez OneDrive." -ForegroundColor Yellow
}

# ─── Calcul taille backup + nombre max à conserver ────────────
$backupSizeBytes = (Get-ChildItem $dest -Recurse | Measure-Object -Property Length -Sum).Sum
if (-not $backupSizeBytes -or $backupSizeBytes -eq 0) { $backupSizeBytes = 5MB }
$maxLocal = Get-MaxBackups $backupSizeBytes

# ─── [4/4] Copie + sync vers la clé USB si présente ──────────
Write-Host "[4/4] Copie vers la cle USB..."
$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1

if ($usbVol) {
    $usbRoot    = "$($usbVol.DriveLetter):\dental-app-backups"
    New-Item -ItemType Directory -Force -Path $usbRoot | Out-Null

    # Sync : copier tous les backups locaux manquants sur la clé
    $localBackups = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue | Sort-Object Name
    $usbNames     = Get-ChildItem $usbRoot -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Name
    $missing      = $localBackups | Where-Object { $_.Name -notin $usbNames }

    foreach ($bkp in $missing) {
        $usbDest = Join-Path $usbRoot $bkp.Name
        Copy-Item $bkp.FullName $usbDest -Recurse -Force
    }

    $copiedCount = $missing.Count
    $maxUsb      = Get-MaxBackups $backupSizeBytes

    # Rotation USB
    $oldUSB = Get-ChildItem $usbRoot -Directory | Sort-Object Name | Select-Object -SkipLast $maxUsb
    foreach ($b in $oldUSB) { Remove-Item $b.FullName -Recurse -Force }

    Write-Host "  OK  USB $($usbVol.DriveLetter):\ ($USB_LABEL) — $copiedCount backup(s) synchronise(s), max $maxUsb gardes" -ForegroundColor Green
} else {
    Write-Host "  INFO  Cle USB non branchee — sera synchronisee automatiquement au prochain branchement." -ForegroundColor Cyan
}

# ─── Rotation locale adaptative ────────────────────────────────
$old = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue |
       Sort-Object Name | Select-Object -SkipLast $maxLocal
foreach ($b in $old) { Remove-Item $b.FullName -Recurse -Force }

# ─── Résumé ────────────────────────────────────────────────────
$backupSizeMB = [math]::Round($backupSizeBytes / 1MB, 1)
Write-Host ""
Write-Host "  BACKUP TERMINE : $timestamp" -ForegroundColor Cyan
Write-Host "  Local    : $dest  ($backupSizeMB MB, max $maxLocal backups)"
if ($onedrivePath) { Write-Host "  OneDrive : $odDest" }
Write-Host ""
