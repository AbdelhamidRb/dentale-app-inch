# stop-app.ps1 - Fermer Dental App

# Chemins derives automatiquement — fonctionne sur C:\, D:\, etc.
$APP_ROOT     = Split-Path $PSScriptRoot                      # X:\laragon\www\dental-app-inch
$LARAGON_ROOT = Split-Path (Split-Path $APP_ROOT)             # X:\laragon
$BACKUP_DIR   = "$(Split-Path $LARAGON_ROOT -Qualifier)\backups\dental-app"
# Detection automatique des chemins Laragon
$mysqlDir  = Get-ChildItem "$LARAGON_ROOT\bin\mysql"  -Directory | Sort-Object Name -Descending | Select-Object -First 1
$apacheDir = Get-ChildItem "$LARAGON_ROOT\bin\apache" -Directory | Sort-Object Name -Descending | Select-Object -First 1

$MYSQLADMIN      = Join-Path $mysqlDir.FullName  "bin\mysqladmin.exe"
$HTTPD           = Join-Path $apacheDir.FullName "bin\httpd.exe"
$BROWSER_PROFILE = "$(Split-Path $LARAGON_ROOT -Qualifier)\dental-app-browser"

# Fermer le navigateur (uniquement le profil dental-app)
foreach ($name in @("msedge", "chrome")) {
    Get-Process -Name $name -ErrorAction SilentlyContinue | ForEach-Object {
        try {
            $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)" -ErrorAction SilentlyContinue).CommandLine
            if ($cmdLine -like "*$([System.IO.Path]::GetFileName($BROWSER_PROFILE))*") {
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
