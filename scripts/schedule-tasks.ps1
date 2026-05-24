# ═══════════════════════════════════════════════════════════════
#  schedule-tasks.ps1  —  Planification des backups automatiques
#  A executer UNE SEULE FOIS en tant qu'Administrateur
#  Lun-Ven 18h00  |  Samedi 12h30
# ═══════════════════════════════════════════════════════════════

$scriptPath = "C:\laragon\www\dental-app-inch\scripts\backup.ps1"
$taskName   = "DentalApp-Backup"

Write-Host ""
Write-Host "Configuration des sauvegardes automatiques..."
Write-Host ""

# Vérifier les droits admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[ERREUR] Ce script doit etre execute en tant qu'Administrateur." -ForegroundColor Red
    Write-Host "  Clic droit sur PowerShell > 'Executer en tant qu'administrateur'"
    exit 1
}

# Supprimer l'ancienne tâche si elle existe
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

# Triggers : Lun-Ven 18h00 + Samedi 12h30
$triggerWeek = New-ScheduledTaskTrigger -Weekly `
    -DaysOfWeek Monday, Tuesday, Wednesday, Thursday, Friday `
    -At "18:00"

$triggerSat = New-ScheduledTaskTrigger -Weekly `
    -DaysOfWeek Saturday `
    -At "12:30"

# Action : lancer backup.ps1 en arrière-plan (fenêtre cachée)
$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$scriptPath`""

# Paramètres : démarrer même si la machine n'était pas allumée à l'heure prévue
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable:$false `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30)

# Créer la tâche
Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $triggerWeek, $triggerSat `
    -Settings $settings `
    -RunLevel Highest `
    -Description "Sauvegarde automatique Dental App (BDD + images)" `
    -Force | Out-Null

Write-Host "  OK  Tache planifiee : $taskName" -ForegroundColor Green
Write-Host "      Lundi au Vendredi  18h00"
Write-Host "      Samedi             12h30"
Write-Host ""
Write-Host "Pour tester maintenant :"
Write-Host "  Start-ScheduledTask -TaskName '$taskName'"
Write-Host ""
Write-Host "Pour voir les logs :"
Write-Host "  Get-ScheduledTaskInfo -TaskName '$taskName'"
Write-Host ""
