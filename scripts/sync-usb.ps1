# ═══════════════════════════════════════════════════════════════
#  sync-usb.ps1  —  Sync automatique vers la clé USB DENTAL-BKP
#  Lancé automatiquement à chaque branchement USB
# ═══════════════════════════════════════════════════════════════

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
$USB_LABEL  = "DENTAL-BKP"

# ─── Paliers : nombre de backups selon taille ─────────────────
function Get-MaxBackups([long]$sizeBytes) {
    $sizeMB = $sizeBytes / 1MB
    if ($sizeMB -lt 50)  { return 30 }
    if ($sizeMB -lt 200) { return 20 }
    if ($sizeMB -lt 500) { return 10 }
    return 5
}

# ─── Vérifier si la clé USB DENTAL-BKP est présente ──────────
$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1

if (-not $usbVol) {
    exit 0
}

$usbRoot = "$($usbVol.DriveLetter):\dental-app-backups"
New-Item -ItemType Directory -Force -Path $usbRoot | Out-Null

# ─── Comparer local vs USB ────────────────────────────────────
$localBackups = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue | Sort-Object Name
$usbNames     = Get-ChildItem $usbRoot -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Name
$missing      = $localBackups | Where-Object { $_.Name -notin $usbNames }

if ($missing.Count -eq 0) {
    exit 0
}

# ─── Notification de démarrage ────────────────────────────────
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show(
    "Clé USB détectée !`n`n$($missing.Count) backup(s) manquant(s) en cours de copie...`n`nNe retirez pas la clé USB.",
    "Dental App — Synchronisation",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null

# ─── Copier les backups manquants ─────────────────────────────
$copied  = 0
$errored = 0

foreach ($bkp in $missing) {
    $usbDest = Join-Path $usbRoot $bkp.Name
    try {
        Copy-Item $bkp.FullName $usbDest -Recurse -Force
        $copied++
    } catch {
        $errored++
    }
}

# ─── Rotation USB adaptative ──────────────────────────────────
$allLocal = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue
if ($allLocal.Count -gt 0) {
    $avgSize = ($allLocal | ForEach-Object {
        (Get-ChildItem $_.FullName -Recurse -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
    } | Measure-Object -Average).Average
    if (-not $avgSize -or $avgSize -eq 0) { $avgSize = 5MB }
} else {
    $avgSize = 5MB
}

$maxUsb = Get-MaxBackups ([long]$avgSize)
$oldUSB = Get-ChildItem $usbRoot -Directory | Sort-Object Name | Select-Object -SkipLast $maxUsb
foreach ($b in $oldUSB) { Remove-Item $b.FullName -Recurse -Force }

# ─── Notification de fin ──────────────────────────────────────
$msg  = "$copied backup(s) copie(s) avec succes."
$msg += "`nMax $maxUsb backups conserves sur la cle."
if ($errored -gt 0) { $msg += "`n$errored backup(s) en erreur." }
$msg += "`n`nVous pouvez retirer la cle USB."

$icon = if ($errored -gt 0) { [System.Windows.Forms.MessageBoxIcon]::Warning } else { [System.Windows.Forms.MessageBoxIcon]::Information }

[System.Windows.Forms.MessageBox]::Show(
    $msg,
    "Dental App — Synchronisation terminee",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    $icon
) | Out-Null
