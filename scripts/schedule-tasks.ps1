# ═══════════════════════════════════════════════════════════════
#  schedule-tasks.ps1  —  Planification des tâches automatiques
#  A executer UNE SEULE FOIS en tant qu'Administrateur
# ═══════════════════════════════════════════════════════════════

$SCRIPTS_DIR  = "C:\laragon\www\dental-app-inch\scripts"
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

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 1 : Backup automatique  (Lun-Ven 18h, Sam 12h30)
# ═══════════════════════════════════════════════════════════════
$taskBackup = "DentalApp-Backup"
Unregister-ScheduledTask -TaskName $taskBackup -Confirm:$false -ErrorAction SilentlyContinue

$triggerWeek = New-ScheduledTaskTrigger -Weekly `
    -DaysOfWeek Monday, Tuesday, Wednesday, Thursday, Friday `
    -At "18:00"

$triggerSat = New-ScheduledTaskTrigger -Weekly `
    -DaysOfWeek Saturday `
    -At "12:30"

$vbs = "$SCRIPTS_DIR\run-hidden.vbs"
$actionBackup = New-ScheduledTaskAction `
    -Execute "wscript.exe" `
    -Argument "//B //NoLogo `"$vbs`" `"$backupScript`""

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable:$false `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30)

Register-ScheduledTask `
    -TaskName $taskBackup `
    -Action $actionBackup `
    -Trigger $triggerWeek, $triggerSat `
    -Settings $settings `
    -RunLevel Highest `
    -Description "Sauvegarde automatique Dental App (BDD + images)" `
    -Force | Out-Null

Write-Host "  OK  $taskBackup" -ForegroundColor Green
Write-Host "      Lundi au Vendredi  18h00"
Write-Host "      Samedi             12h30"
Write-Host ""

# ═══════════════════════════════════════════════════════════════
#  TÂCHE 2 : Sync USB automatique (déclenché par branchement USB)
# ═══════════════════════════════════════════════════════════════
$taskUSB = "DentalApp-USBSync"
Unregister-ScheduledTask -TaskName $taskUSB -Confirm:$false -ErrorAction SilentlyContinue

# Trigger basé sur l'événement Windows "périphérique USB connecté"
# Event Log: Microsoft-Windows-DriverFrameworks-UserMode/Operational, ID 2003
$usbTriggerXml = @'
<QueryList>
  <Query Id="0" Path="Microsoft-Windows-DriverFrameworks-UserMode/Operational">
    <Select Path="Microsoft-Windows-DriverFrameworks-UserMode/Operational">
      *[System[Provider[@Name='Microsoft-Windows-DriverFrameworks-UserMode'] and EventID=2003]]
    </Select>
  </Query>
</QueryList>
'@

$actionUSB = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$usbScript`""

# Créer la tâche via XML pour supporter le trigger événement USB
$taskXml = @"
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
    <DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries>
    <StopIfGoingOnBatteries>false</StopIfGoingOnBatteries>
    <ExecutionTimeLimit>PT10M</ExecutionTimeLimit>
    <Enabled>true</Enabled>
  </Settings>
  <Actions>
    <Exec>
      <Command>wscript.exe</Command>
      <Arguments>//B //NoLogo "$vbs" "$usbScript"</Arguments>
    </Exec>
  </Actions>
</Task>
"@

Register-ScheduledTask -TaskName $taskUSB -Xml $taskXml -Force | Out-Null

Write-Host "  OK  $taskUSB" -ForegroundColor Green
Write-Host "      Declenche automatiquement a chaque branchement USB"
Write-Host "      Synchronise les backups manquants vers la cle DENTAL-BKP"
Write-Host ""

# ===============================================================
#  TACHE 3 : Laravel Scheduler (toutes les minutes)
#  Necessaire pour l'archivage automatique des patients
# ===============================================================
$taskScheduler = "DentalApp-Scheduler"
Unregister-ScheduledTask -TaskName $taskScheduler -Confirm:$false -ErrorAction SilentlyContinue

$phpDir  = Get-ChildItem "C:\laragon\bin\php" -Directory | Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1
$PHP     = Join-Path $phpDir.FullName "php.exe"
$artisan = "C:\laragon\www\dental-app-inch\artisan"

$triggerScheduler = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At "00:00"
$actionScheduler  = New-ScheduledTaskAction `
    -Execute $PHP `
    -Argument "`"$artisan`" schedule:run" `
    -WorkingDirectory $APP_DIR

$settingsScheduler = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 2)

Register-ScheduledTask `
    -TaskName $taskScheduler `
    -Action $actionScheduler `
    -Trigger $triggerScheduler `
    -Settings $settingsScheduler `
    -RunLevel Highest `
    -Description "Laravel scheduler - archivage automatique patients a 01h00" `
    -Force | Out-Null

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
