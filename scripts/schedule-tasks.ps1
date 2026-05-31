# ═══════════════════════════════════════════════════════════════
#  schedule-tasks.ps1  —  Planification des tâches automatiques
#  A executer UNE SEULE FOIS en tant qu'Administrateur
# ═══════════════════════════════════════════════════════════════

$APP_DIR      = "C:\laragon\www\dental-app-inch"
$SCRIPTS_DIR  = "$APP_DIR\scripts"
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
$phpObj = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue |
          Where-Object { $_.Name -match '^php-8\.' } |
          Sort-Object Name -Descending | Select-Object -First 1
$PHP    = if ($phpObj) { Join-Path $phpObj.FullName "php.exe" } else { "php" }
$artisan = "$APP_DIR\artisan"

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 1 : Backup automatique (Lun-Ven 18h, Sam 12h30)
#  Tourne en tant que SYSTEM — aucune fenetre possible
# ═══════════════════════════════════════════════════════════════
$taskBackup = "DentalApp-Backup"
Unregister-ScheduledTask -TaskName $taskBackup -Confirm:$false -ErrorAction SilentlyContinue

$backupXml = @"
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo>
    <Description>Sauvegarde automatique Dental App (BDD + images)</Description>
  </RegistrationInfo>
  <Triggers>
    <CalendarTrigger>
      <StartBoundary>2024-01-01T18:00:00</StartBoundary>
      <Enabled>true</Enabled>
      <ScheduleByWeek>
        <WeeksInterval>1</WeeksInterval>
        <DaysOfWeek><Monday/><Tuesday/><Wednesday/><Thursday/><Friday/></DaysOfWeek>
      </ScheduleByWeek>
    </CalendarTrigger>
    <CalendarTrigger>
      <StartBoundary>2024-01-06T12:30:00</StartBoundary>
      <Enabled>true</Enabled>
      <ScheduleByWeek>
        <WeeksInterval>1</WeeksInterval>
        <DaysOfWeek><Saturday/></DaysOfWeek>
      </ScheduleByWeek>
    </CalendarTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <UserId>S-1-5-18</UserId>
      <LogonType>ServiceAccount</LogonType>
      <RunLevel>HighestAvailable</RunLevel>
    </Principal>
  </Principals>
  <Settings>
    <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
    <Hidden>true</Hidden>
    <StartWhenAvailable>true</StartWhenAvailable>
    <ExecutionTimeLimit>PT30M</ExecutionTimeLimit>
    <Enabled>true</Enabled>
  </Settings>
  <Actions>
    <Exec>
      <Command>powershell.exe</Command>
      <Arguments>-NonInteractive -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "$backupScript"</Arguments>
    </Exec>
  </Actions>
</Task>
"@

Register-ScheduledTask -TaskName $taskBackup -Xml $backupXml -Force | Out-Null

Write-Host "  OK  $taskBackup" -ForegroundColor Green
Write-Host "      Lundi au Vendredi  18h00"
Write-Host "      Samedi             12h30"
Write-Host ""

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 2 : Sync USB automatique (branchement USB)
#  Tourne en mode interactif (besoin de MessageBox)
#  Hidden=true garantit aucun flash de terminal
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
      <Command>powershell.exe</Command>
      <Arguments>-NonInteractive -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "$usbScript"</Arguments>
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
#  Tourne en tant que SYSTEM — aucune fenetre possible
# ═══════════════════════════════════════════════════════════════
$taskScheduler = "DentalApp-Scheduler"
Unregister-ScheduledTask -TaskName $taskScheduler -Confirm:$false -ErrorAction SilentlyContinue

$schedulerXml = @"
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo>
    <Description>Laravel scheduler - archivage automatique patients a 01h00</Description>
  </RegistrationInfo>
  <Triggers>
    <CalendarTrigger>
      <Repetition>
        <Interval>PT1M</Interval>
        <StopAtDurationEnd>false</StopAtDurationEnd>
      </Repetition>
      <StartBoundary>2024-01-01T00:00:00</StartBoundary>
      <Enabled>true</Enabled>
    </CalendarTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <UserId>S-1-5-18</UserId>
      <LogonType>ServiceAccount</LogonType>
      <RunLevel>HighestAvailable</RunLevel>
    </Principal>
  </Principals>
  <Settings>
    <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
    <Hidden>true</Hidden>
    <StartWhenAvailable>true</StartWhenAvailable>
    <ExecutionTimeLimit>PT2M</ExecutionTimeLimit>
    <Enabled>true</Enabled>
  </Settings>
  <Actions>
    <Exec>
      <Command>$PHP</Command>
      <Arguments>"$artisan" schedule:run</Arguments>
      <WorkingDirectory>$APP_DIR</WorkingDirectory>
    </Exec>
  </Actions>
</Task>
"@

Register-ScheduledTask -TaskName $taskScheduler -Xml $schedulerXml -Force | Out-Null

Write-Host "  OK  $taskScheduler" -ForegroundColor Green
Write-Host "      Chaque minute - archivage automatique a 01h00"
Write-Host ""

# ═══════════════════════════════════════════════════════════════
Write-Host "Toutes les taches sont configurees !" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour tester le backup maintenant :"
Write-Host "  Start-ScheduledTask -TaskName '$taskBackup'"
Write-Host ""
Write-Host "Pour tester la sync USB (branchez d'abord la cle DENTAL-BKP) :"
Write-Host "  Start-ScheduledTask -TaskName '$taskUSB'"
Write-Host ""
Write-Host "Pour tester l archivage maintenant :"
Write-Host "  Start-ScheduledTask -TaskName '$taskScheduler'"
Write-Host ""
