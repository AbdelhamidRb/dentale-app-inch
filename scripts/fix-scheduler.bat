@echo off
:: Creer la tache Laravel Scheduler manquante
:: Clic droit -> Executer en tant qu'administrateur

:: Detecter chemins automatiquement
pushd "%~dp0.."
set APP=%CD%
popd
pushd "%~dp0..\.."
set LARAGON_ROOT=%CD%
popd

echo.
echo ================================================
echo   Creation tache Laravel Scheduler
echo   App : %APP%
echo ================================================
echo.

powershell -ExecutionPolicy Bypass -Command ^
  "$LARAGON_ROOT = '%LARAGON_ROOT%'; ^
   $APP = '%APP%'; ^
   $phpDir = Get-ChildItem \"$LARAGON_ROOT\bin\php\" -Directory | Where-Object { $_.Name -match '^php-8\.' } | Sort-Object Name -Descending | Select-Object -First 1; ^
   $PHP = Join-Path $phpDir.FullName 'php.exe'; ^
   $action = New-ScheduledTaskAction -Execute $PHP -Argument \"`\"$APP\artisan`\" schedule:run\" -WorkingDirectory $APP; ^
   $trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date); ^
   $settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 1) -MultipleInstances IgnoreNew -StartWhenAvailable; ^
   Register-ScheduledTask -TaskName 'DentalApp-Scheduler' -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force | Out-Null; ^
   Write-Host 'OK - Tache DentalApp-Scheduler creee' -ForegroundColor Green; ^
   $info = Get-ScheduledTaskInfo -TaskName 'DentalApp-Scheduler'; ^
   Write-Host \"Prochaine execution : $($info.NextRunTime)\""

echo.
pause
