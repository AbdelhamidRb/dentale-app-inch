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
4. Double-clic sur `INSTALLER.bat` → tout se fait automatiquement
5. Le navigateur s'ouvre sur l'app → installation terminée ✅

**Comptes par défaut à remettre au client :**
| Rôle | Email | Mot de passe |
|---|---|---|
| Dentiste | dentiste@demo.com | password |
| Assistant | assistant@demo.com | password |

> Demander au client de **changer les mots de passe** dès la première connexion.

---

### Étape 5 — Raccourcis Bureau (créés automatiquement par INSTALLER.bat)
| Fichier | Action |
|---|---|
| `DEMARRER.bat` | Démarre l'app (Apache + MySQL + navigateur) |
| `FERMER.bat` | Ferme l'app (ferme la fenêtre + arrête les services) |
| `BACKUP.bat` | Sauvegarde manuelle |
| `RESTAURER.bat` | Restauration depuis un backup |

---

## UTILISATION QUOTIDIENNE (par le client)

```
Matin  → double-clic DEMARRER  → l'app s'ouvre
Soir   → double-clic FERMER    → l'app se ferme
```

Les backups sont automatiques :
- Lundi au Vendredi à **18h00**
- Samedi à **12h30**

Backup manuel : double-clic `BACKUP.bat` ou Paramètres → Sauvegardes dans l'app.

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

## SI LE CLIENT A UN BUG (fix à distance)

1. **Connexion via AnyDesk** (demander au client d'installer AnyDesk)
2. Corriger le code sur le repo GitHub
3. Depuis le PC du client, lancer :
   ```
   double-clic → scripts\UPDATE.bat  (à créer sur le bureau si besoin)
   ```
   Ou manuellement :
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

## COMMANDES UTILES

```powershell
# Générer une licence client
php artisan license:generate "email" "mac" "Nom Cabinet"

# Voir les backups disponibles
Get-ChildItem C:\backups\dental-app

# Tester la tâche de backup manuellement
Start-ScheduledTask -TaskName "DentalApp-Backup"

# Voir le statut de la tâche planifiée
Get-ScheduledTaskInfo -TaskName "DentalApp-Backup"
```
