# ═══════════════════════════════════════════════════════════════
#  restore.ps1  —  Restauration d'un backup  |  Dental App
#  Liste les backups disponibles (local + USB) et restaure
#  BDD + images au choix du dentiste
# ═══════════════════════════════════════════════════════════════

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
# ─── Configuration ─────────────────────────────────────────────
$USB_LABEL  = "DENTAL-BKP"

# Lire DB depuis .env
function Get-EnvVal($key) {
    $line = Get-Content "$APP_ROOT\.env" -ErrorAction SilentlyContinue |
            Where-Object { $_ -match "^$key=" } | Select-Object -First 1
    if ($line) { return ($line -split '=', 2)[1].Trim('"').Trim("'") }
    return ""
}
$DB_NAME = Get-EnvVal "DB_DATABASE"
$DB_USER = Get-EnvVal "DB_USERNAME"
$DB_PASS = Get-EnvVal "DB_PASSWORD"
if (-not $DB_NAME) { $DB_NAME = "dental_db_inch" }
if (-not $DB_USER) { $DB_USER = "root" }

# Auto-détecter MySQL
$mysqlDir = Get-ChildItem "$LARAGON_ROOT\bin\mysql" -Directory | Sort-Object Name -Descending | Select-Object -First 1
$mysql    = Join-Path $mysqlDir.FullName "bin\mysql.exe"

# ─── Verifier mysql.exe ────────────────────────────────────────
if (-not $mysqlDir -or -not (Test-Path $mysql)) {
    Write-Host "[ERREUR] mysql.exe introuvable dans $LARAGON_ROOT\bin\mysql" -ForegroundColor Red
    exit 1
}

# ─── Collecter les backups disponibles ─────────────────────────
$backups = [System.Collections.Generic.List[PSCustomObject]]::new()

if (Test-Path $BACKUP_DIR) {
    Get-ChildItem $BACKUP_DIR -Directory | Sort-Object Name -Descending | ForEach-Object {
        $backups.Add([PSCustomObject]@{ Name = $_.Name; Path = $_.FullName; Source = "Local" })
    }
}

$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1
if ($usbVol) {
    $usbRoot = "$($usbVol.DriveLetter):\dental-app-backups"
    if (Test-Path $usbRoot) {
        $localNames = $backups | ForEach-Object { $_.Name }
        Get-ChildItem $usbRoot -Directory | Sort-Object Name -Descending | ForEach-Object {
            $folderName = $_.Name
            if ($localNames -notcontains $folderName) {
                $backups.Add([PSCustomObject]@{ Name = $folderName; Path = $_.FullName; Source = "USB" })
            }
        }
    }
}

$backups = $backups | Sort-Object Name -Descending

if ($backups.Count -eq 0) {
    Write-Host "[ERREUR] Aucun backup trouve (ni local ni USB)." -ForegroundColor Red
    Write-Host "  Local : $BACKUP_DIR"
    Write-Host "  USB   : etiquette '$USB_LABEL'"
    exit 1
}

# ─── Menu de selection ─────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "  RESTAURATION -- Dental App"
Write-Host "=================================================="
Write-Host ""
Write-Host "  Backups disponibles :"
Write-Host ""

$limit = [Math]::Min($backups.Count, 20)
for ($i = 0; $i -lt $limit; $i++) {
    $b = $backups[$i]
    if ($i -eq 0) {
        $mark = " <- le plus recent"
    } else {
        $mark = ""
    }
    Write-Host ("  [{0,2}]  {1}   [{2}]{3}" -f ($i + 1), $b.Name, $b.Source, $mark)
}
Write-Host ""

$choice = Read-Host "Numero du backup a restaurer (ou q pour quitter)"
if ($choice -match '^q') { Write-Host "Annule."; exit 0 }

$idx = -1
[int]::TryParse($choice, [ref]$idx) | Out-Null
$idx = $idx - 1

if ($idx -lt 0 -or $idx -ge $backups.Count) {
    Write-Host "[ERREUR] Choix invalide." -ForegroundColor Red
    exit 1
}

$selected = $backups[$idx]

# ─── Confirmation ──────────────────────────────────────────────
Write-Host ""
Write-Host ("  Backup selectionne : {0}  [{1}]" -f $selected.Name, $selected.Source) -ForegroundColor Yellow
Write-Host ""
Write-Host "  ATTENTION : toutes les donnees actuelles seront ecrasees." -ForegroundColor Red
Write-Host ""
$confirm = Read-Host "Tapez OUI pour confirmer"
if ($confirm -ne 'OUI') { Write-Host "Restauration annulee."; exit 0 }

Write-Host ""

# ─── Restaurer la BDD ──────────────────────────────────────────
$sqlFile = Join-Path $selected.Path "database.sql"
if (Test-Path $sqlFile) {
    Write-Host "[1/2] Restauration de la base de donnees..."
    $sqlContent = [System.IO.File]::ReadAllText($sqlFile, [System.Text.Encoding]::UTF8)

    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName               = $mysql
    $psi.Arguments              = "-u $DB_USER -p$DB_PASS $DB_NAME"
    $psi.RedirectStandardInput  = $true
    $psi.RedirectStandardError  = $true
    $psi.UseShellExecute        = $false
    $psi.CreateNoWindow         = $true

    $proc = [System.Diagnostics.Process]::Start($psi)
    $proc.StandardInput.Write($sqlContent)
    $proc.StandardInput.Close()
    $errors = $proc.StandardError.ReadToEnd()
    $proc.WaitForExit()

    if ($proc.ExitCode -eq 0) {
        Write-Host "  OK  Base de donnees restauree" -ForegroundColor Green
    } else {
        Write-Host "  [ERREUR] Echec restauration MySQL — images NON modifiees." -ForegroundColor Red
        if ($errors) { Write-Host "  $errors" -ForegroundColor Red }
        exit 1
    }
} else {
    Write-Host "  INFO  Pas de fichier SQL dans ce backup (ignore)"
}

# ─── Restaurer les images ──────────────────────────────────────
$zipFile = Join-Path $selected.Path "images.zip"
if (Test-Path $zipFile) {
    Write-Host "[2/2] Restauration des images patients..."
    $imagesPath = Join-Path $APP_ROOT "storage\app\public\patients"

    if (Test-Path $imagesPath) { Remove-Item $imagesPath -Recurse -Force }
    New-Item -ItemType Directory -Force -Path $imagesPath | Out-Null
    Expand-Archive -Path $zipFile -DestinationPath $imagesPath -Force
    Write-Host "  OK  Images restaurees" -ForegroundColor Green
} else {
    Write-Host "  INFO  Pas d'images dans ce backup (ignore)"
}

# ─── Resume ────────────────────────────────────────────────────
Write-Host ""
Write-Host "  RESTAURATION TERMINEE" -ForegroundColor Cyan
Write-Host ("  Backup utilise : {0}" -f $selected.Name)
Write-Host ""
Write-Host "  Rechargez l'application dans le navigateur."
Write-Host ""
