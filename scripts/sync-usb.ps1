# ═══════════════════════════════════════════════════════════════
#  sync-usb.ps1  —  Sync automatique vers la clé USB DENTAL-BKP
#  Lancé automatiquement à chaque branchement USB
# ═══════════════════════════════════════════════════════════════

$BACKUP_DIR = "C:\backups\dental-app"
$USB_LABEL  = "DENTAL-BKP"
$MAX_USB    = 30

# ─── Vérifier si la clé USB DENTAL-BKP est présente ──────────
$usbVol = Get-Volume | Where-Object { $_.FileSystemLabel -eq $USB_LABEL } | Select-Object -First 1

if (-not $usbVol) {
    # Pas la bonne clé → on quitte silencieusement
    exit 0
}

$usbRoot = "$($usbVol.DriveLetter):\backups\dental-app"
New-Item -ItemType Directory -Force -Path $usbRoot | Out-Null

# ─── Comparer local vs USB ────────────────────────────────────
$localBackups = Get-ChildItem $BACKUP_DIR -Directory -ErrorAction SilentlyContinue | Sort-Object Name
$usbBackups   = Get-ChildItem $usbRoot -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Name

$missing = $localBackups | Where-Object { $_.Name -notin $usbBackups }

if ($missing.Count -eq 0) {
    # Tout est déjà synchronisé
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

# ─── Copier les backups manquants ────────────────────────────
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

# ─── Supprimer les anciens sur la clé (garder 30) ────────────
$oldUSB = Get-ChildItem $usbRoot -Directory | Sort-Object Name | Select-Object -SkipLast $MAX_USB
foreach ($b in $oldUSB) { Remove-Item $b.FullName -Recurse -Force }

# ─── Notification de fin ──────────────────────────────────────
$msg = "$copied backup(s) copie(s) avec succes."
if ($errored -gt 0) { $msg += "`n$errored backup(s) en erreur." }
$msg += "`n`nVous pouvez retirer la cle USB."

$icon = if ($errored -gt 0) { [System.Windows.Forms.MessageBoxIcon]::Warning } else { [System.Windows.Forms.MessageBoxIcon]::Information }

[System.Windows.Forms.MessageBox]::Show(
    $msg,
    "Dental App — Synchronisation terminee",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    $icon
) | Out-Null
