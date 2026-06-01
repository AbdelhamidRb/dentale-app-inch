Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# Icone personnalisee dental.ico (fournie dans le dossier scripts)
$icoPath = "$LARAGON_ROOT\www\dental-app-inch\scripts\dental.ico"
if (-not (Test-Path $icoPath)) {
    Write-Host "ERREUR : dental.ico introuvable dans scripts/" -ForegroundColor Red
    exit 1
}
Write-Host "OK  dental.ico charge"

# ─── Compiler DentalApp.exe avec l'icône ──────────────────────
$code = @"
using System;
using System.Drawing;
using System.Windows.Forms;
using System.Diagnostics;

public class DentalApp : Form
{
    private Button btnStart;
    private Button btnStop;

    public DentalApp()
    {
        this.Text            = "Dental App";
        this.Size            = new Size(320, 260);
        this.StartPosition   = FormStartPosition.CenterScreen;
        this.FormBorderStyle = FormBorderStyle.FixedDialog;
        this.MaximizeBox     = false;
        this.BackColor       = Color.FromArgb(248, 250, 252);

        Label title = new Label();
        title.Text      = "Dental App";
        title.Font      = new Font("Segoe UI", 16, FontStyle.Bold);
        title.ForeColor = Color.FromArgb(15, 23, 42);
        title.Size      = new Size(280, 36);
        title.Location  = new Point(20, 24);
        title.TextAlign = ContentAlignment.MiddleCenter;
        this.Controls.Add(title);

        Label sub = new Label();
        sub.Text      = "Cabinet dentaire";
        sub.Font      = new Font("Segoe UI", 9);
        sub.ForeColor = Color.FromArgb(148, 163, 184);
        sub.Size      = new Size(280, 20);
        sub.Location  = new Point(20, 62);
        sub.TextAlign = ContentAlignment.MiddleCenter;
        this.Controls.Add(sub);

        btnStart = new Button();
        btnStart.Text      = "Demarrer l'application";
        btnStart.Font      = new Font("Segoe UI", 11, FontStyle.Bold);
        btnStart.Size      = new Size(260, 52);
        btnStart.Location  = new Point(30, 105);
        btnStart.FlatStyle = FlatStyle.Flat;
        btnStart.FlatAppearance.BorderSize = 0;
        btnStart.BackColor = Color.FromArgb(37, 99, 235);
        btnStart.ForeColor = Color.White;
        btnStart.Cursor    = Cursors.Hand;
        btnStart.Click    += new EventHandler(BtnStart_Click);
        this.Controls.Add(btnStart);

        btnStop = new Button();
        btnStop.Text      = "Fermer l'application";
        btnStop.Font      = new Font("Segoe UI", 11, FontStyle.Bold);
        btnStop.Size      = new Size(260, 52);
        btnStop.Location  = new Point(30, 170);
        btnStop.FlatStyle = FlatStyle.Flat;
        btnStop.FlatAppearance.BorderSize = 0;
        btnStop.BackColor = Color.FromArgb(254, 226, 226);
        btnStop.ForeColor = Color.FromArgb(220, 38, 38);
        btnStop.Cursor    = Cursors.Hand;
        btnStop.Click    += new EventHandler(BtnStop_Click);
        this.Controls.Add(btnStop);
    }

    private void RunScript(string script)
    {
        string vbs = @"$LARAGON_ROOT\www\dental-app-inch\scripts\run-hidden.vbs";
        ProcessStartInfo psi = new ProcessStartInfo();
        psi.FileName        = "wscript.exe";
        psi.Arguments       = "//B //NoLogo \"" + vbs + "\" \"" + script + "\"";
        psi.UseShellExecute = false;
        psi.CreateNoWindow  = true;
        Process.Start(psi);
    }

    private void BtnStart_Click(object sender, EventArgs e) {
        RunScript(@"$LARAGON_ROOT\www\dental-app-inch\scripts\start-app.ps1");
    }
    private void BtnStop_Click(object sender, EventArgs e) {
        RunScript(@"$LARAGON_ROOT\www\dental-app-inch\scripts\stop-app.ps1");
    }

    [STAThread]
    public static void Main() {
        var principal = new System.Security.Principal.WindowsPrincipal(
            System.Security.Principal.WindowsIdentity.GetCurrent());
        if (!principal.IsInRole(System.Security.Principal.WindowsBuiltInRole.Administrator)) {
            ProcessStartInfo psi2 = new ProcessStartInfo();
            psi2.FileName        = Application.ExecutablePath;
            psi2.Verb            = "runas";
            psi2.UseShellExecute = true;
            try { Process.Start(psi2); } catch { }
            return;
        }
        Application.EnableVisualStyles();
        Application.SetCompatibleTextRenderingDefault(false);
        Application.Run(new DentalApp());
    }
}
"@

$output   = "$LARAGON_ROOT\www\dental-app-inch\scripts\DentalApp.exe"
$provider = New-Object Microsoft.CSharp.CSharpCodeProvider
$params   = New-Object System.CodeDom.Compiler.CompilerParameters
$params.OutputAssembly = $output
$params.CompilerOptions = "/target:winexe /win32icon:`"$icoPath`""
$params.ReferencedAssemblies.AddRange(@("System.Windows.Forms.dll","System.Drawing.dll","System.dll"))

$result = $provider.CompileAssemblyFromSource($params, $code)
if ($result.Errors.Count -gt 0) {
    $result.Errors | ForEach-Object { Write-Host "ERREUR: $_" -ForegroundColor Red }
    exit 1
}
Write-Host "OK  DentalApp.exe compile avec icone"

# ─── Copier sur le Bureau (OneDrive ou classique) ──────────────
$desktop = "$env:USERPROFILE\OneDrive\Desktop"
if (-not (Test-Path $desktop)) { $desktop = "$env:USERPROFILE\OneDrive\Bureau" }
if (-not (Test-Path $desktop)) { $desktop = "$env:USERPROFILE\Desktop" }
if (-not (Test-Path $desktop)) { $desktop = "$env:USERPROFILE\Bureau" }
if (-not (Test-Path $desktop)) { $desktop = [Environment]::GetFolderPath("Desktop") }
Copy-Item $output "$desktop\DentalApp.exe" -Force

# ─── Créer raccourcis .lnk pour BACKUP et RESTAURER ───────────
$shell = New-Object -ComObject WScript.Shell

# Raccourci BACKUP
$lnk = $shell.CreateShortcut("$desktop\Backup.lnk")
$lnk.TargetPath       = "$LARAGON_ROOT\www\dental-app-inch\scripts\BACKUP.bat"
$lnk.IconLocation     = "C:\Windows\System32\imageres.dll,174"   # icône disque/sauvegarde
$lnk.WindowStyle      = 7   # minimisé (pas de fenêtre noire)
$lnk.Description      = "Sauvegarde Dental App"
$lnk.Save()

# Raccourci RESTAURER
$lnk2 = $shell.CreateShortcut("$desktop\Restaurer.lnk")
$lnk2.TargetPath      = "$LARAGON_ROOT\www\dental-app-inch\scripts\RESTAURER.bat"
$lnk2.IconLocation    = "C:\Windows\System32\imageres.dll,176"   # icône restauration
$lnk2.WindowStyle     = 7
$lnk2.Description     = "Restauration Dental App"
$lnk2.Save()

Write-Host "OK  Raccourcis crees sur le Bureau"
Write-Host ""
Write-Host "Bureau mis a jour :"
Write-Host "  DentalApp.exe    (demarrer / fermer l'app)"
Write-Host "  Backup.lnk       (sauvegarde)"
Write-Host "  Restaurer.lnk    (restauration)"
