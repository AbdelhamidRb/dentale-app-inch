Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# ─── Créer l'icône (dent bleue) ────────────────────────────────
$bmp = New-Object Drawing.Bitmap(32, 32)
$g   = [Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.Clear([Drawing.Color]::Transparent)

# Fond bleu rond
$g.FillEllipse(
    (New-Object Drawing.SolidBrush([Drawing.Color]::FromArgb(37,99,235))),
    0, 0, 31, 31
)

# Forme dent blanche (rectangle arrondi en bas + bosses en haut)
$white = New-Object Drawing.SolidBrush([Drawing.Color]::White)
# Corps de la dent
$g.FillRectangle($white, 9, 14, 14, 12)
# Bosses du haut (3 ronds)
$g.FillEllipse($white, 7,  10, 6, 8)
$g.FillEllipse($white, 13, 8,  6, 8)
$g.FillEllipse($white, 19, 10, 6, 8)
# Racines
$g.FillRectangle($white, 9,  24, 4, 4)
$g.FillRectangle($white, 19, 24, 4, 4)

$g.Dispose()

# Sauvegarder en .ico
$icoPath = "C:\laragon\www\dental-app-inch\scripts\dental.ico"
$icon    = [Drawing.Icon]::FromHandle($bmp.GetHicon())
$fs      = [IO.File]::Open($icoPath, [IO.FileMode]::Create)
$icon.Save($fs)
$fs.Close()
$bmp.Dispose()

Write-Host "OK  dental.ico cree"

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
        ProcessStartInfo psi = new ProcessStartInfo();
        psi.FileName        = "powershell.exe";
        psi.Arguments       = "-ExecutionPolicy Bypass -WindowStyle Hidden -File \"" + script + "\"";
        psi.CreateNoWindow  = true;
        psi.UseShellExecute = false;
        Process.Start(psi);
    }

    private void BtnStart_Click(object sender, EventArgs e) {
        RunScript(@"C:\laragon\www\dental-app-inch\scripts\start-app.ps1");
    }
    private void BtnStop_Click(object sender, EventArgs e) {
        RunScript(@"C:\laragon\www\dental-app-inch\scripts\stop-app.ps1");
    }

    [STAThread]
    public static void Main() {
        Application.EnableVisualStyles();
        Application.SetCompatibleTextRenderingDefault(false);
        Application.Run(new DentalApp());
    }
}
"@

$output   = "C:\laragon\www\dental-app-inch\scripts\DentalApp.exe"
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

# ─── Copier sur le Bureau ──────────────────────────────────────
$desktop = "$env:USERPROFILE\Desktop"
Copy-Item $output "$desktop\DentalApp.exe" -Force

# ─── Créer raccourcis .lnk pour BACKUP et RESTAURER ───────────
$shell = New-Object -ComObject WScript.Shell

# Raccourci BACKUP
$lnk = $shell.CreateShortcut("$desktop\Backup.lnk")
$lnk.TargetPath       = "C:\laragon\www\dental-app-inch\scripts\BACKUP.bat"
$lnk.IconLocation     = "C:\Windows\System32\imageres.dll,174"   # icône disque/sauvegarde
$lnk.WindowStyle      = 7   # minimisé (pas de fenêtre noire)
$lnk.Description      = "Sauvegarde Dental App"
$lnk.Save()

# Raccourci RESTAURER
$lnk2 = $shell.CreateShortcut("$desktop\Restaurer.lnk")
$lnk2.TargetPath      = "C:\laragon\www\dental-app-inch\scripts\RESTAURER.bat"
$lnk2.IconLocation    = "C:\Windows\System32\imageres.dll,176"   # icône restauration
$lnk2.WindowStyle     = 7
$lnk2.Description     = "Restauration Dental App"
$lnk2.Save()

Write-Host "OK  Raccourcis crees sur le Bureau"
Write-Host ""
Write-Host "Bureau mis a jour :"
Write-Host "  DentalApp.exe  (icone dent bleue)"
Write-Host "  Backup.lnk     (icone sauvegarde)"
Write-Host "  Restaurer.lnk  (icone restauration)"
