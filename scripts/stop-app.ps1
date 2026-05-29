# stop-app.ps1 - Fermer Dental App

# Detection automatique des chemins Laragon
$mysqlDir  = Get-ChildItem "C:\laragon\bin\mysql"  -Directory | Sort-Object Name -Descending | Select-Object -First 1
$apacheDir = Get-ChildItem "C:\laragon\bin\apache" -Directory | Sort-Object Name -Descending | Select-Object -First 1

$MYSQLADMIN = Join-Path $mysqlDir.FullName  "bin\mysqladmin.exe"
$HTTPD      = Join-Path $apacheDir.FullName "bin\httpd.exe"
$PROFILE    = "C:\dental-app-browser"

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

# Arreter MySQL proprement puis Apache
& $MYSQLADMIN -u root shutdown 2>$null
Start-Sleep -Seconds 1
& $HTTPD -k stop 2>&1 | Out-Null
Start-Sleep -Seconds 1

# Forcer si toujours actifs
Stop-Process -Name "mysqld","httpd" -Force -ErrorAction SilentlyContinue
