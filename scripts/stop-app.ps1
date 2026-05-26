# stop-app.ps1 - Fermer Dental App

$PROFILE = "C:\dental-app-browser"

# Fermer le navigateur (uniquement le profil dental-app)
foreach ($name in @("msedge", "chrome")) {
    Get-Process -Name $name -ErrorAction SilentlyContinue | ForEach-Object {
        try {
            $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)" -ErrorAction SilentlyContinue).CommandLine
            if ($cmdLine -like "*dental-app-browser*") {
                $_.CloseMainWindow() | Out-Null
                Start-Sleep -Milliseconds 500
                if (-not $_.HasExited) { $_.Kill() }
            }
        } catch { }
    }
}

# Arreter les services et liberer les ressources
Stop-Service "DentalMySQL"  -Force -ErrorAction SilentlyContinue
Stop-Service "DentalApache" -Force -ErrorAction SilentlyContinue
