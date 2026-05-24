# ═══════════════════════════════════════════════════════════════
#  stop-app.ps1  —  Fermer Dental App
# ═══════════════════════════════════════════════════════════════

$MYSQLADMIN = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe"
$HTTPD      = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$PROFILE    = "C:\dental-app-browser"

# ─── Fermer la fenêtre de l'app (Edge ou Chrome avec notre profil)
# On ferme proprement uniquement le processus qui utilise notre profil isolé
$browserNames = @("msedge", "chrome")
foreach ($name in $browserNames) {
    Get-Process -Name $name -ErrorAction SilentlyContinue | ForEach-Object {
        try {
            # Vérifie si ce processus utilise notre profil dental-app
            $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)" -ErrorAction SilentlyContinue).CommandLine
            if ($cmdLine -like "*dental-app-browser*") {
                $_.CloseMainWindow() | Out-Null
                Start-Sleep -Milliseconds 500
                if (-not $_.HasExited) { $_.Kill() }
            }
        } catch { }
    }
}

# ─── Fermer MySQL proprement ────────────────────────────────────
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName              = $MYSQLADMIN
$psi.Arguments             = "-u root shutdown"
$psi.UseShellExecute       = $false
$psi.CreateNoWindow        = $true
$psi.RedirectStandardError = $true
$proc = [System.Diagnostics.Process]::Start($psi)
$proc.WaitForExit()

# ─── Fermer Apache proprement ───────────────────────────────────
$psi2 = New-Object System.Diagnostics.ProcessStartInfo
$psi2.FileName             = $HTTPD
$psi2.Arguments            = "-k stop"
$psi2.UseShellExecute      = $false
$psi2.CreateNoWindow       = $true
$proc2 = [System.Diagnostics.Process]::Start($psi2)
$proc2.WaitForExit()

# ─── Sécurité : forcer si toujours actifs ──────────────────────
Start-Sleep -Milliseconds 800
Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
Stop-Process -Name "httpd"  -Force -ErrorAction SilentlyContinue
