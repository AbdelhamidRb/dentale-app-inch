# ═══════════════════════════════════════════════════════════════
#  schedule-tasks.ps1  —  Planification des tâches automatiques
#  A executer UNE SEULE FOIS en tant qu'Administrateur
# ═══════════════════════════════════════════════════════════════

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
$SCRIPTS_DIR  = "$APP_ROOT\scripts"
$backupScript = "$SCRIPTS_DIR\backup.ps1"
$usbScript    = "$SCRIPTS_DIR\sync-usb.ps1"

Write-Host ""
Write-Host "Configuration des taches automatiques..."
Write-Host ""

$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[ERREUR] Ce script doit etre execute en tant qu'Administrateur." -ForegroundColor Red
    exit 1
}

# Detecter PHP
$phpObj = Get-ChildItem "$LARAGON_ROOT\bin\php" -Directory -ErrorAction SilentlyContinue |
          Where-Object { $_.Name -match '^php-8\.' } |
          Sort-Object Name -Descending | Select-Object -First 1
$PHP    = if ($phpObj) { Join-Path $phpObj.FullName "php.exe" } else { "php" }
$artisan = "$APP_ROOT\artisan"

$sysPrincipal  = New-ScheduledTaskPrincipal -UserId "SYSTEM" -RunLevel Highest -LogonType ServiceAccount
$userPrincipal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest

# Supprimer l'ancienne tâche backup planifiée (remplacée par backup au démarrage)
Unregister-ScheduledTask -TaskName "DentalApp-Backup" -Confirm:$false -ErrorAction SilentlyContinue

$vbsLauncher = "$SCRIPTS_DIR\run-hidden.vbs"

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 1 : Sync USB (branchement USB, interactif pour MessageBox)
# ═══════════════════════════════════════════════════════════════
$taskUSB = "DentalApp-USBSync"
Unregister-ScheduledTask -TaskName $taskUSB -Confirm:$false -ErrorAction SilentlyContinue

$usbXml = @"
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo>
    <Description>Synchronisation automatique des backups vers la cle USB DENTAL-BKP</Description>
  </RegistrationInfo>
  <Triggers>
    <EventTrigger>
      <Delay>PT8S</Delay>
      <Subscription>&lt;QueryList&gt;&lt;Query Id=&quot;0&quot; Path=&quot;Microsoft-Windows-DriverFrameworks-UserMode/Operational&quot;&gt;&lt;Select Path=&quot;Microsoft-Windows-DriverFrameworks-UserMode/Operational&quot;&gt;*[System[Provider[@Name=&quot;Microsoft-Windows-DriverFrameworks-UserMode&quot;] and EventID=2003]]&lt;/Select&gt;&lt;/Query&gt;&lt;/QueryList&gt;</Subscription>
    </EventTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <LogonType>InteractiveToken</LogonType>
      <RunLevel>HighestAvailable</RunLevel>
    </Principal>
  </Principals>
  <Settings>
    <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
    <Hidden>true</Hidden>
    <StartWhenAvailable>true</StartWhenAvailable>
    <ExecutionTimeLimit>PT10M</ExecutionTimeLimit>
    <Enabled>true</Enabled>
  </Settings>
  <Actions>
    <Exec>
      <Command>wscript.exe</Command>
      <Arguments>//B //NoLogo "$vbsLauncher" "$usbScript"</Arguments>
    </Exec>
  </Actions>
</Task>
"@

Register-ScheduledTask -TaskName $taskUSB -Xml $usbXml -Force | Out-Null

Write-Host "  OK  $taskUSB" -ForegroundColor Green
Write-Host "      Declenche automatiquement a chaque branchement USB"
Write-Host "      Synchronise les backups manquants vers la cle DENTAL-BKP"
Write-Host ""

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 3 : Laravel Scheduler (toutes les minutes)
#  SYSTEM — aucune fenetre possible
# ═══════════════════════════════════════════════════════════════
$taskScheduler = "DentalApp-Scheduler"
Unregister-ScheduledTask -TaskName $taskScheduler -Confirm:$false -ErrorAction SilentlyContinue

$trigSched = New-ScheduledTaskTrigger `
    -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At "00:00"
$actSched = New-ScheduledTaskAction `
    -Execute $PHP `
    -Argument "`"$artisan`" schedule:run"
$setsSched = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 2) `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $taskScheduler `
    -Action $actSched `
    -Trigger $trigSched `
    -Settings $setsSched `
    -Principal $sysPrincipal `
    -Force | Out-Null

Write-Host "  OK  $taskScheduler" -ForegroundColor Green
Write-Host "      Chaque minute - archivage automatique a 01h00"
Write-Host ""

# ═══════════════════════════════════════════════════════════════
Write-Host "Toutes les taches sont configurees !" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour tester la sync USB (branchez d'abord la cle DENTAL-BKP) :"
Write-Host "  Start-ScheduledTask -TaskName '$taskUSB'"
Write-Host ""
Write-Host "Pour tester l archivage maintenant :"
Write-Host "  Start-ScheduledTask -TaskName '$taskScheduler'"
Write-Host ""
