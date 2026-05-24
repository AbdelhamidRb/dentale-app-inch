# ═══════════════════════════════════════════════════════════════
#  update-ip.ps1  —  Mettre à jour l'IP réseau dans Apache
#  À lancer si l'adresse IP du PC a changé (nouveau réseau WiFi)
# ═══════════════════════════════════════════════════════════════

$CONF_FILE = "C:\laragon\etc\apache2\sites-enabled\auto.dental-app-inch.test.conf"
$HTTPD     = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"

Add-Type -AssemblyName System.Windows.Forms

# Détecter la nouvelle IP
$localIP = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -match 'Wi-Fi|Ethernet|Local Area' -and $_.IPAddress -notmatch '^127\.' } |
    Sort-Object InterfaceMetric |
    Select-Object -First 1).IPAddress

if (-not $localIP) {
    [System.Windows.Forms.MessageBox]::Show(
        "Aucune connexion réseau détectée.",
        "Dental App", [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Warning
    ) | Out-Null
    exit 1
}

if (Test-Path $CONF_FILE) {
    $conf = Get-Content $CONF_FILE -Raw

    # Remplacer l'ancien bloc IP
    $conf = $conf -replace '(?s)\r?\n# Accès réseau local.*?</VirtualHost>', ''

    $ipBlock = @"

# Accès réseau local (assistante, téléphones)
<VirtualHost ${localIP}:80>
    DocumentRoot "C:/laragon/www/dental-app-inch/public"
    ServerName $localIP
    <Directory "C:/laragon/www/dental-app-inch/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@

    $conf = $conf.TrimEnd() + "`r`n" + $ipBlock
    Set-Content $CONF_FILE $conf -Encoding UTF8

    # Recharger Apache si actif
    $apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
    if ($apacheRunning) {
        Start-Process -FilePath $HTTPD -ArgumentList "-k graceful" -WindowStyle Hidden -Wait
    }
}

[System.Windows.Forms.MessageBox]::Show(
    "IP mise à jour !`n`nNouvelle adresse réseau : http://$localIP`n`nPartagez cette adresse avec l'assistante et les téléphones.",
    "Dental App — Mise à jour IP",
    [System.Windows.Forms.MessageBoxButtons]::OK,
    [System.Windows.Forms.MessageBoxIcon]::Information
) | Out-Null
