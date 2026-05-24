# ═══════════════════════════════════════════════════════════════
#  set-static-ip.ps1  —  Fixer l'IP locale du PC (WiFi/Ethernet)
#  Doit être lancé en Administrateur
# ═══════════════════════════════════════════════════════════════

Add-Type -AssemblyName System.Windows.Forms

function ShowMsg($msg, $title, $icon) {
    [System.Windows.Forms.MessageBox]::Show($msg, $title,
        [System.Windows.Forms.MessageBoxButtons]::OK, $icon) | Out-Null
}

# ─── Vérifier droits admin ─────────────────────────────────────
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    ShowMsg "Ce script doit être lancé en Administrateur.`n`nFaites clic droit sur le script → Exécuter en tant qu'administrateur." "Dental App" ([System.Windows.Forms.MessageBoxIcon]::Warning)
    exit 1
}

# ─── Trouver l'interface réseau active ────────────────────────
$adapter = Get-NetIPConfiguration |
    Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } |
    Select-Object -First 1

if (-not $adapter) {
    ShowMsg "Aucune connexion réseau active détectée.`n`nConnectez le PC au WiFi du cabinet et relancez ce script." "Dental App" ([System.Windows.Forms.MessageBoxIcon]::Error)
    exit 1
}

$interfaceAlias = $adapter.InterfaceAlias
$currentIP      = $adapter.IPv4Address.IPAddress
$prefixLen      = $adapter.IPv4Address.PrefixLength
$gateway        = $adapter.IPv4DefaultGateway.NextHop
$dns            = ($adapter.DNSServer | Where-Object { $_.AddressFamily -eq 2 } | Select-Object -ExpandProperty ServerAddresses -First 1)
if (-not $dns) { $dns = "8.8.8.8" }

# ─── Vérifier si déjà en IP fixe ──────────────────────────────
$ipConfig = Get-NetIPAddress -InterfaceAlias $interfaceAlias -AddressFamily IPv4 -ErrorAction SilentlyContinue
if ($ipConfig.PrefixOrigin -eq 'Manual') {
    ShowMsg "L'IP est déjà fixe sur cette interface.`n`nAdresse : $currentIP`n`nL'assistante peut utiliser : http://$currentIP" "Dental App — IP déjà fixe" ([System.Windows.Forms.MessageBoxIcon]::Information)
    exit 0
}

# ─── Confirmation avant de changer ────────────────────────────
$confirm = [System.Windows.Forms.MessageBox]::Show(
    "Fixer l'IP du PC à : $currentIP`n`nInterface : $interfaceAlias`nPasserelle : $gateway`nDNS : $dns`n`nL'assistante pourra toujours utiliser :`nhttp://$currentIP`n`nContinuer ?",
    "Dental App — IP fixe",
    [System.Windows.Forms.MessageBoxButtons]::OKCancel,
    [System.Windows.Forms.MessageBoxIcon]::Question
)
if ($confirm -ne [System.Windows.Forms.DialogResult]::OK) { exit 0 }

# ─── Supprimer l'ancienne config DHCP et appliquer l'IP fixe ──
try {
    # Retirer l'IP DHCP actuelle
    Remove-NetIPAddress -InterfaceAlias $interfaceAlias -AddressFamily IPv4 -Confirm:$false -ErrorAction SilentlyContinue
    Remove-NetRoute -InterfaceAlias $interfaceAlias -AddressFamily IPv4 -Confirm:$false -ErrorAction SilentlyContinue

    # Appliquer l'IP fixe avec la même valeur qu'avant
    New-NetIPAddress -InterfaceAlias $interfaceAlias -AddressFamily IPv4 -IPAddress $currentIP -PrefixLength $prefixLen -DefaultGateway $gateway -ErrorAction Stop | Out-Null

    # DNS
    Set-DnsClientServerAddress -InterfaceAlias $interfaceAlias -ServerAddresses $dns | Out-Null

    ShowMsg "IP fixée avec succès !`n`nAdresse permanente : http://$currentIP`n`nMettez cette adresse en favori sur :`n  - Téléphone du dentiste`n  - Téléphone de l'assistante`n  - PC de l'assistante" "Dental App — Succès" ([System.Windows.Forms.MessageBoxIcon]::Information)

} catch {
    ShowMsg "Erreur lors de la configuration :`n$_`n`nVérifiez que vous êtes bien connecté au WiFi du cabinet." "Dental App — Erreur" ([System.Windows.Forms.MessageBoxIcon]::Error)
    exit 1
}
