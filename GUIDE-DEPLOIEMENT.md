# Guide de déploiement — Dental App

---

## À FAIRE UNE SEULE FOIS (sur ton PC de développement)

### 1. Générer les clés RSA
```powershell
cd C:\laragon\www\dental-app-inch
php artisan license:keys
```
Cela crée :
- `private.pem` → **clé secrète, sur ton PC uniquement**
- `storage/app/license.pub` → clé publique, incluse dans l'app

> **Ne jamais refaire cette étape** sauf si tu perds `private.pem`.
> Si tu régénères les clés, toutes les licences clients déjà générées deviennent invalides.

---

## POUR CHAQUE NOUVEAU CLIENT

### Étape 1 — Récupérer les infos du client
Tu as besoin de :
- Son **email**
- L'**adresse MAC** de son PC (la carte réseau principale)

Pour obtenir le MAC depuis son PC :
```
cmd → getmac /fo csv /nh
```
Prendre la première ligne (ex: `"E0-2B-E9-73-19-C3"`)

---

### Étape 2 — Générer sa licence
```powershell
php artisan license:generate "email@client.com" "e0:2b:e9:73:19:c3" "Nom du Cabinet"
```
Cela crée `dental-app.lic` dans le dossier du projet.

---

### Étape 3 — Préparer la clé USB d'installation
Mettre sur une clé USB :
```
clé USB/
├── Laragon-full-x64.exe        ← télécharger sur laragon.org (version Full)
├── dental-app.lic              ← généré à l'étape 2
└── INSTALLER.bat               ← C:\laragon\www\dental-app-inch\scripts\INSTALLER.bat
```

---

### Étape 4 — Installation chez le client

1. Installer **Laragon Full** (double-clic `Laragon-full-x64.exe` → next next finish)
2. Ouvrir Laragon → cliquer **Start All** (Apache + MySQL doivent être verts)
3. Placer `dental-app.lic` dans le même dossier que `INSTALLER.bat`
4. **Clic droit sur `INSTALLER.bat` → "Exécuter en tant qu'administrateur"** → tout se fait automatiquement
5. À la fin, une fenêtre affiche l'IP réseau du cabinet (ex: `192.168.1.100`) → **noter cette IP**
6. L'app s'ouvre dans le navigateur ✅

**Comptes par défaut à remettre au client :**
| Rôle | Email | Mot de passe |
|---|---|---|
| Dentiste | dentiste@demo.com | password |
| Assistant | assistant@demo.com | password |

> Demander au client de **changer les mots de passe** dès la première connexion.

---

### Étape 5 — Configurer l'accès réseau (assistante + téléphones)

L'installation fixe automatiquement l'IP du PC (si lancée en admin).

**Sur chaque appareil du cabinet (téléphone dentiste, téléphone assistante, PC assistante) :**
1. Ouvrir le navigateur
2. Taper `http://192.168.1.100` (l'IP affichée à l'étape 4)
3. Se connecter → **mettre en favori**

> L'IP ne changera plus jamais. Un seul réglage à faire.

---

### Étape 6 — Configurer OneDrive (backup cloud automatique)

1. Sur le PC du dentiste, ouvrir **OneDrive** (déjà installé sur Windows 10/11)
2. Se connecter avec son compte Microsoft
3. C'est tout — les backups se copieront automatiquement dans `OneDrive/DentalApp-Backups/`

> OneDrive gratuit = 5 Go. Les backups prennent ~200 Mo max. Largement suffisant.

---

### Étape 7 — Préparer la clé USB de backup

1. Brancher une clé USB sur le PC du dentiste
2. Faire **clic droit → Formater** → changer le nom (label) en : `DENTAL-BKP`
3. Débrancher et remettre au dentiste

> À partir de là, chaque fois que le dentiste branche cette clé, les backups manquants se copient automatiquement dessus avec une notification.

---

### Étape 8 — Raccourcis Bureau (créés automatiquement)
| Fichier | Action |
|---|---|
| `DentalApp.exe` | Démarre ou ferme l'app (double-clic) |
| `BACKUP.bat` | Sauvegarde manuelle immédiate |
| `RESTAURER.bat` | Restauration depuis un backup |
| `Fixer IP Reseau.lnk` | À utiliser si l'installation n'était pas en admin |

---

## UTILISATION QUOTIDIENNE (par le client)

```
Matin  → double-clic DentalApp.exe → bouton "Démarrer"  → l'app s'ouvre
Soir   → double-clic DentalApp.exe → bouton "Fermer"    → l'app se ferme
```

**Backups automatiques :**
- Lundi au Vendredi à **18h00**
- Samedi à **12h30**
- Destinations : local (30 derniers) + OneDrive (dynamique selon taille) + USB (sync auto au branchement)

**Backup manuel :** double-clic `BACKUP.bat` ou Paramètres → Sauvegardes dans l'app.

**Sync USB :** brancher la clé `DENTAL-BKP` → les backups manquants se copient automatiquement.

---

## SI LE CLIENT CHANGE DE PC

1. Récupérer le nouveau MAC :
   ```
   getmac /fo csv /nh
   ```
2. Regénérer une nouvelle licence :
   ```powershell
   php artisan license:generate "email@client.com" "nouveau-mac" "Nom du Cabinet"
   ```
3. Envoyer le nouveau `dental-app.lic` au client
4. Le client remplace l'ancien fichier dans `C:\laragon\www\dental-app-inch\`
5. Recharger l'app ✅

---

## SI LE CLIENT CHANGE DE RÉSEAU WIFI

Si le cabinet change de box internet ou de réseau WiFi, l'IP peut changer.

1. Sur le Bureau du PC dentiste, double-clic sur **`Fixer IP Reseau.lnk`**
2. Clic droit → Exécuter en tant qu'administrateur
3. Une popup affiche la nouvelle IP → la communiquer à l'assistante
4. L'assistante met à jour son favori

---

## SI LE CLIENT A UN BUG (fix à distance)

1. **Connexion via AnyDesk** (demander au client d'installer AnyDesk)
2. Corriger le code sur le repo GitHub
3. Depuis le PC du client, lancer :
   ```powershell
   C:\laragon\www\dental-app-inch\scripts\update.ps1
   ```
   Ce script : backup automatique → git pull → migrations

---

## CHOSES À NE JAMAIS FAIRE ❌

| Action interdite | Pourquoi |
|---|---|
| Mettre `private.pem` sur GitHub | N'importe qui pourrait générer des licences |
| Partager `private.pem` par WhatsApp/email | Même raison |
| Mettre `dental-app.lic` sur GitHub | Licences volables et réutilisables |
| Regénérer les clés RSA sans raison | Invalide toutes les licences clients existantes |
| Donner `private.pem` au client | Il pourrait générer sa propre licence ou la partager |
| Supprimer `private.pem` sans backup | Impossible de générer de nouvelles licences |
| Formater la clé USB sans backup | Perte de tous les backups USB |

---

## SAUVEGARDE DE TES CLÉS (important)

`private.pem` est irremplaçable. Garde-en une copie :
- Sur une **clé USB personnelle** (pas celle du client)
- Dans un **coffre-fort de mots de passe** (ex: Bitwarden, KeePass)

```powershell
# Copier private.pem sur une clé USB de sauvegarde
Copy-Item "C:\laragon\www\dental-app-inch\private.pem" "E:\backup-dev\private.pem"
```

---

## RÉSUMÉ DES FICHIERS IMPORTANTS

| Fichier | Où | Partageable ? |
|---|---|---|
| `private.pem` | Racine du projet (ton PC uniquement) | ❌ Non |
| `storage/app/license.pub` | Dans le repo GitHub | ✅ Oui |
| `dental-app.lic` | Racine du projet client | ⚠️ Client uniquement |
| `.env` | Racine du projet client | ❌ Non |
| `scripts/backup.ps1` | Repo GitHub | ✅ Oui |

---

## RÉSUMÉ DE LA STRATÉGIE DE BACKUP

| Destination | Nombre gardé | Déclenchement |
|---|---|---|
| Local `C:\backups\dental-app\` | 30 derniers | Automatique (18h/12h30) + manuel |
| OneDrive `DentalApp-Backups/` | Dynamique (1 Go max, min 5, max 60) | Automatique en même temps |
| Clé USB `DENTAL-BKP` | 30 derniers | Automatique au branchement de la clé |

---

## COMMANDES UTILES

```powershell
# Générer une licence client
php artisan license:generate "email" "mac" "Nom Cabinet"

# Voir les backups disponibles
Get-ChildItem C:\backups\dental-app

# Tester le backup manuellement
Start-ScheduledTask -TaskName "DentalApp-Backup"

# Tester la sync USB (brancher d'abord la clé DENTAL-BKP)
Start-ScheduledTask -TaskName "DentalApp-USBSync"

# Voir le statut des tâches planifiées
Get-ScheduledTaskInfo -TaskName "DentalApp-Backup"
Get-ScheduledTaskInfo -TaskName "DentalApp-USBSync"
```
