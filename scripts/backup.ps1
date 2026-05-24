# ═══════════════════════════════════════════════════════════════
#  backup.ps1  —  Sauvegarde automatique  |  Dental App
#  Destinations : local (30) + OneDrive (dynamique) + USB (sync auto)
# ═══════════════════════════════════════════════════════════════

$DB_NAME    = "dental_db_inch"
$DB_USER    = "root"
$DB_PASS    = "hamid2003"
$APP_ROOT   = "C:\laragon\www\dental-app-inch"
$BACKUP_DIR = "C:\backups\dental-app"
$USB_LABEL  = "DENTAL-BKP"
$MAX_LOCAL  = 30
$MYSQL_BIN  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin"
$mysqldump  = "$MYSQL_BIN\mysqldump.exe"

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

# ─── [4/4] Copie vers la clé USB si présente ──────────────────
Write-Host "[4/4] Copie vers la cle USB..."
$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1

if ($usbVol) {
    $usbRoot = "$($usbVol.DriveLetter):\backups\dental-app"
    $usbDest = Join-Path $usbRoot $timestamp
    New-Item -ItemType Directory -Force -Path $usbDest | Out-Null
    Copy-Item -Path "$dest\*" -Destination $usbDest -Recurse -Force
    Write-Host "  OK  USB $($usbVol.DriveLetter):\ ($USB_LABEL)" -ForegroundColor Green

    # Garder les 30 derniers sur la clé aussi
    $oldUSB = Get-ChildItem $usbRoot -Directory | Sort-Object Name | Select-Object -SkipLast $MAX_LOCAL
    foreach ($b in $oldUSB) { Remove-Item $b.FullName -Recurse -Force }
} else {
    Write-Host "  INFO  Cle USB non branchee — sera synchronisee automatiquement au prochain branchement." -ForegroundColor Cyan
}

# ─── Supprimer anciens backups locaux (garder 30) ──────────────
$old = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue |
       Sort-Object Name | Select-Object -SkipLast $MAX_LOCAL
foreach ($b in $old) { Remove-Item $b.FullName -Recurse -Force }

# ─── Résumé ────────────────────────────────────────────────────
Write-Host ""
Write-Host "  BACKUP TERMINE : $timestamp" -ForegroundColor Cyan
Write-Host "  Local    : $dest"
if ($onedrivePath) { Write-Host "  OneDrive : $odDest" }
Write-Host ""
