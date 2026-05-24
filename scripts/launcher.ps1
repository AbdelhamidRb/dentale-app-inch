Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$START = "C:\laragon\www\dental-app-inch\scripts\start-app.ps1"
$STOP  = "C:\laragon\www\dental-app-inch\scripts\stop-app.ps1"

$form = New-Object Windows.Forms.Form
$form.Text            = "Dental App"
$form.Size            = New-Object Drawing.Size(320, 260)
$form.StartPosition   = "CenterScreen"
$form.FormBorderStyle = "FixedDialog"
$form.MaximizeBox     = $false
$form.BackColor       = [Drawing.Color]::FromArgb(248, 250, 252)

$title = New-Object Windows.Forms.Label
$title.Text      = "Dental App"
$title.Font      = New-Object Drawing.Font("Segoe UI", 16, [Drawing.FontStyle]::Bold)
$title.ForeColor = [Drawing.Color]::FromArgb(15, 23, 42)
$title.Size      = New-Object Drawing.Size(280, 36)
$title.Location  = New-Object Drawing.Point(20, 24)
$title.TextAlign = "MiddleCenter"
$form.Controls.Add($title)

$sub = New-Object Windows.Forms.Label
$sub.Text      = "Cabinet dentaire"
$sub.Font      = New-Object Drawing.Font("Segoe UI", 9)
$sub.ForeColor = [Drawing.Color]::FromArgb(148, 163, 184)
$sub.Size      = New-Object Drawing.Size(280, 20)
$sub.Location  = New-Object Drawing.Point(20, 62)
$sub.TextAlign = "MiddleCenter"
$form.Controls.Add($sub)

$btnStart = New-Object Windows.Forms.Button
$btnStart.Text      = "Demarrer l'application"
$btnStart.Font      = New-Object Drawing.Font("Segoe UI", 11, [Drawing.FontStyle]::Bold)
$btnStart.Size      = New-Object Drawing.Size(260, 52)
$btnStart.Location  = New-Object Drawing.Point(30, 105)
$btnStart.FlatStyle = "Flat"
$btnStart.FlatAppearance.BorderSize = 0
$btnStart.BackColor = [Drawing.Color]::FromArgb(37, 99, 235)
$btnStart.ForeColor = [Drawing.Color]::White
$btnStart.Cursor    = "Hand"
$btnStart.Add_Click({
    Start-Process powershell.exe -ArgumentList "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$START`""
})
$form.Controls.Add($btnStart)

$btnStop = New-Object Windows.Forms.Button
$btnStop.Text      = "Fermer l'application"
$btnStop.Font      = New-Object Drawing.Font("Segoe UI", 11, [Drawing.FontStyle]::Bold)
$btnStop.Size      = New-Object Drawing.Size(260, 52)
$btnStop.Location  = New-Object Drawing.Point(30, 170)
$btnStop.FlatStyle = "Flat"
$btnStop.FlatAppearance.BorderSize = 0
$btnStop.BackColor = [Drawing.Color]::FromArgb(254, 226, 226)
$btnStop.ForeColor = [Drawing.Color]::FromArgb(220, 38, 38)
$btnStop.Cursor    = "Hand"
$btnStop.Add_Click({
    Start-Process powershell.exe -ArgumentList "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$STOP`""
})
$form.Controls.Add($btnStop)

$form.ShowDialog()
