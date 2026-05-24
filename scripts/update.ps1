# ═══════════════════════════════════════════════════════════════
#  update.ps1  —  Mise à jour de l'application  |  Dental App
#  1. Backup automatique
#  2. git pull depuis GitHub (branche main)
#  3. php artisan migrate --force
# ═══════════════════════════════════════════════════════════════

# ─── Configuration ─────────────────────────────────────────────
$APP_ROOT = "C:\laragon\www\dental-app-inch"

# ─── Trouver PHP ───────────────────────────────────────────────
$php = Get-ChildItem "C:\laragon\bin\php" -Filter "php.exe" -Recurse `
    -ErrorAction SilentlyContinue | Sort-Object FullName -Descending |
    Select-Object -First 1 -ExpandProperty FullName
if (-not $php) {
    Write-Host "[ERREUR] php.exe introuvable dans C:\laragon\bin\php" -ForegroundColor Red
    exit 1
}

# ─── Vérifier git ──────────────────────────────────────────────
$gitCmd = Get-Command git -ErrorAction SilentlyContinue
if (-not $gitCmd) {
    Write-Host "[ERREUR] Git n'est pas installe ou pas dans le PATH." -ForegroundColor Red
    Write-Host "  Telechargez Git : https://git-scm.com/download/win"
    exit 1
}

Write-Host ""
Write-Host "══════════════════════════════════════════════════"
Write-Host "  MISE A JOUR — Dental App"
Write-Host "══════════════════════════════════════════════════"
Write-Host ""

# ─── Étape 1 : Backup préalable ────────────────────────────────
Write-Host "[1/3] Sauvegarde avant mise a jour..."
& "$APP_ROOT\scripts\backup.ps1"
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERREUR] Backup échoué. Mise à jour annulée." -ForegroundColor Red
    exit 1
}

# ─── Étape 2 : git pull ────────────────────────────────────────
Write-Host ""
Write-Host "[2/3] Telechargement de la mise a jour (git pull)..."
Set-Location $APP_ROOT
git pull origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERREUR] git pull a echoue." -ForegroundColor Red
    Write-Host "  Verifiez votre connexion internet et vos droits GitHub."
    exit 1
}
Write-Host "  OK  Code mis a jour" -ForegroundColor Green

# ─── Étape 3 : Migrations ──────────────────────────────────────
Write-Host ""
Write-Host "[3/3] Mise a jour de la base de donnees..."
& $php "$APP_ROOT\artisan" migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERREUR] Migrations echouees." -ForegroundColor Red
    exit 1
}
Write-Host "  OK  Base de donnees a jour" -ForegroundColor Green

# ─── Résumé ────────────────────────────────────────────────────
Write-Host ""
Write-Host "  MISE A JOUR TERMINEE" -ForegroundColor Cyan
Write-Host "  Rechargez la page dans le navigateur."
Write-Host ""
