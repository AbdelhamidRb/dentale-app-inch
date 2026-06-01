@echo off
:: Créer la tâche Laravel Scheduler manquante
:: Clic droit -> Exécuter en tant qu'administrateur

echo.
echo ================================================
echo   Creation tache Laravel Scheduler
echo ================================================
echo.

powershell -ExecutionPolicy Bypass -Command ^
  "$phpDir = Get-ChildItem 'C:\laragon\bin\php' -Directory | Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1; ^
   $PHP = Join-Path $phpDir.FullName 'php.exe'; ^
   $APP = 'C:\laragon\www\dental-app-inch'; ^
   $action = New-ScheduledTaskAction -Execute $PHP -Argument \"`\"$APP\artisan`\" schedule:run\" -WorkingDirectory $APP; ^
   $trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date); ^
   $settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 1) -MultipleInstances IgnoreNew -StartWhenAvailable; ^
   Register-ScheduledTask -TaskName 'DentalApp-Scheduler' -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force; ^
   Write-Host 'OK - Tache DentalApp-Scheduler creee' -ForegroundColor Green; ^
   $info = Get-ScheduledTaskInfo -TaskName 'DentalApp-Scheduler'; ^
   Write-Host \"Prochaine execution : $($info.NextRunTime)\""

echo.
pause
