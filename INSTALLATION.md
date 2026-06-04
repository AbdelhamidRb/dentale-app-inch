# Guide d'installation — DentalApp

## Prérequis

- PC Windows 10/11
- Connexion internet
- Fichiers fournis par le développeur :
  - `INSTALLER.bat`
  - `install.ps1`
  - `dental-app.lic`
  - `dental-app-token.txt`

---

## Étape 1 — Installer Laragon

1. Télécharger Laragon depuis **laragon.org**
2. Installer avec les options par défaut
3. Ouvrir Laragon → cliquer **Start All**
4. Vérifier que les indicateurs Apache et MySQL sont **verts**

---

## Étape 2 — Lancer l'installateur

1. Copier les 4 fichiers fournis dans le même dossier (ex: Bureau)
2. Clic droit sur `INSTALLER.bat` → **Exécuter en tant qu'administrateur**
3. Renseigner les informations demandées :

```
Compte DENTISTE :
  Nom complet   : ex. Dr. Salim Benali
  Email         : ex. salim@gmail.com
  Mot de passe  : min. 8 caractères

Compte ASSISTANTE :
  Nom complet   : ex. Sara Idrissi
  Email         : ex. sara@gmail.com
  Mot de passe  : min. 8 caractères
```

4. Attendre la fin — le script fait automatiquement :
   - Téléchargement du code depuis GitHub
   - Configuration PHP + Apache
   - Création de la base de données
   - Migrations et comptes utilisateurs
   - Création de `DentalApp.exe` sur le Bureau

---

## Étape 3 — Fixer l'adresse IP (important)

L'adresse IP doit être fixe pour que l'assistante puisse toujours accéder à l'app depuis son téléphone.

**3.1 — Voir l'IP actuelle et la passerelle**

Ouvrir PowerShell en administrateur et taper :

```powershell
Get-NetIPConfiguration | Where-Object { $_.IPv4Address -and $_.IPv4Address.IPAddress -notmatch '^127\.' } | Select-Object InterfaceAlias, @{n='IP';e={$_.IPv4Address.IPAddress}}, @{n='Passerelle';e={$_.IPv4DefaultGateway.NextHop}}
```

**3.2 — Fixer l'IP**

Remplacer les valeurs selon le résultat obtenu :

```powershell
# Exemple avec IP 192.168.1.100, passerelle 192.168.1.1, interface "Wi-Fi"
New-NetIPAddress -InterfaceAlias "Wi-Fi" -IPAddress "192.168.1.100" -PrefixLength 24 -DefaultGateway "192.168.1.1"
Set-DnsClientServerAddress -InterfaceAlias "Wi-Fi" -ServerAddresses ("8.8.8.8","8.8.4.4")
```

> **Note :** Garder la même IP qu'avant (voir résultat étape 3.1) pour ne pas interrompre le réseau.

---

## Étape 4 — Désactiver la veille

Le PC doit rester actif pour que l'assistante puisse toujours accéder à l'app.

```powershell
powercfg /change standby-timeout-ac 0
```

---

## Étape 5 — Supprimer la tâche obsolète

```powershell
Unregister-ScheduledTask -TaskName "DentalApp-Scheduler" -Confirm:$false -ErrorAction SilentlyContinue
```

---

## Étape 6 — Vérifier les tâches planifiées

```powershell
Get-ScheduledTask | Where-Object { $_.TaskName -match "dental" } | Select-Object TaskName, State
```

Résultat attendu — une seule tâche :

```
TaskName          State
--------          -----
DentalApp-USBSync Ready
```

---

## Étape 7 — Test final

1. Double-clic sur `DentalApp.exe` sur le Bureau
2. L'app s'ouvre dans le navigateur
3. Se connecter avec les identifiants du dentiste
4. Depuis le téléphone de l'assistante, aller sur `http://[IP-FIXEE]`
5. Se connecter avec les identifiants de l'assistante

---

## Étape 8 — Changer les mots de passe

> **Important :** Changer les mots de passe après la première connexion.

- Dentiste → **Mon profil** → Changer le mot de passe
- Assistante → **Mon profil** → Changer le mot de passe

---

## Clé USB de sauvegarde (optionnel)

Pour activer les sauvegardes automatiques sur clé USB :

1. Formater une clé USB en **FAT32** ou **NTFS**
2. La renommer **`DENTAL-BKP`** (exactement, en majuscules)
3. La brancher au PC — les sauvegardes se copient automatiquement à chaque branchement

---

## Récapitulatif accès

| Qui | Adresse |
|-----|---------|
| Dentiste (sur le PC) | `http://dental-app-inch.test` |
| Assistante (téléphone/tablette) | `http://[IP-FIXEE]` |

---

## En cas de problème

| Problème | Solution |
|----------|----------|
| Page blanche / erreur | Ouvrir Laragon → Start All |
| Assistante ne peut pas accéder | Vérifier que le PC et le téléphone sont sur le même WiFi |
| Mot de passe oublié | Contacter le développeur |
| Mise à jour disponible | Ouvrir l'app → Paramètres → Mise à jour |
