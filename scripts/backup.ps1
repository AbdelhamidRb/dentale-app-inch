# ═══════════════════════════════════════════════════════════════
#  backup.ps1  —  Sauvegarde automatique  |  Dental App
#  Sauvegarde : BDD (.sql) + Images patients (.zip)
#  Destination : local C:\backups\dental-app\  +  cle USB
# ═══════════════════════════════════════════════════════════════

# ─── Configuration ─────────────────────────────────────────────
$DB_NAME     = "dental_db_inch"
$DB_USER     = "root"
$DB_PASS     = "hamid2003"
$APP_ROOT    = "C:\laragon\www\dental-app-inch"
$BACKUP_DIR  = "C:\backups\dental-app"
$USB_LABEL   = "DENTAL-BKP"
$MAX_BACKUPS = 30
$MYSQL_BIN   = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin"
$mysqldump   = "$MYSQL_BIN\mysqldump.exe"

# ─── Verifier mysqldump ────────────────────────────────────────
if (-not (Test-Path $mysqldump)) {
    Write-Host "[ERREUR] mysqldump.exe introuvable : $mysqldump" -ForegroundColor Red
    exit 1
}

# ─── Banniere ──────────────────────────────────────────────────
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm"
Write-Host ""
Write-Host "=================================================="
Write-Host "  BACKUP Dental App -- $timestamp"
Write-Host "=================================================="
Write-Host ""

# ─── Creer le dossier de backup ────────────────────────────────
$dest = Join-Path $BACKUP_DIR $timestamp
New-Item -ItemType Directory -Force -Path $dest | Out-Null

# ─── Export BDD via Start-Process (le plus fiable) ─────────────
Write-Host "[1/3] Sauvegarde de la base de donnees..."
$sqlFile = Join-Path $dest "database.sql"

$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName               = $mysqldump
$psi.Arguments              = "-u $DB_USER -p$DB_PASS --single-transaction --default-character-set=utf8mb4 --routines $DB_NAME"
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError  = $true
$psi.UseShellExecute        = $false
$psi.CreateNoWindow         = $true

$proc = [System.Diagnostics.Process]::Start($psi)
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

# ─── Compresser les images ─────────────────────────────────────
Write-Host "[2/3] Compression des images patients..."
$imagesPath = Join-Path $APP_ROOT "storage\app\public\patients"
$zipFile    = Join-Path $dest "images.zip"

if (Test-Path $imagesPath) {
    Compress-Archive -Path $imagesPath -DestinationPath $zipFile -Force
    $sizeIMG = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)
    Write-Host "  OK  images.zip  ($sizeIMG MB)" -ForegroundColor Green
} else {
    Write-Host "  INFO  Aucune image trouvee (ignore)"
}

# ─── Copier vers la cle USB ────────────────────────────────────
Write-Host "[3/3] Copie vers la cle USB..."
$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1

if ($usbVol) {
    $usbRoot = "$($usbVol.DriveLetter):\backups\dental-app"
    $usbDest = Join-Path $usbRoot $timestamp
    New-Item -ItemType Directory -Force -Path $usbDest | Out-Null
    Copy-Item -Path "$dest\*" -Destination $usbDest -Recurse -Force
    Write-Host "  OK  Copie sur $($usbVol.DriveLetter):\ ($USB_LABEL)" -ForegroundColor Green

    $old = Get-ChildItem $usbRoot -Directory | Sort-Object Name | Select-Object -SkipLast $MAX_BACKUPS
    foreach ($b in $old) { Remove-Item $b.FullName -Recurse -Force }
} else {
    Write-Host "  ATTENTION  Cle USB '$USB_LABEL' non trouvee. Backup local uniquement." -ForegroundColor Yellow
}

# ─── Supprimer anciens backups locaux ──────────────────────────
$old = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue |
       Sort-Object Name | Select-Object -SkipLast $MAX_BACKUPS
foreach ($b in $old) {
    Remove-Item $b.FullName -Recurse -Force
    Write-Host "  Supprime ancien backup : $($b.Name)"
}

# ─── Resume ────────────────────────────────────────────────────
Write-Host ""
Write-Host "  BACKUP TERMINE : $timestamp" -ForegroundColor Cyan
Write-Host "  Emplacement : $dest"
Write-Host ""
