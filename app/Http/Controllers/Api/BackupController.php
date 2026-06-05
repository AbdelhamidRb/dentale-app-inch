<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupController extends Controller
{
    private function dbName(): string { return config('database.connections.mysql.database', 'dental_db_inch'); }
    private function dbUser(): string { return config('database.connections.mysql.username', 'root'); }
    private function dbPass(): string { return config('database.connections.mysql.password', ''); }

    // Detecte le dossier Laragon depuis base_path() — fonctionne sur C:\, D:\, etc.
    private function laragonRoot(): string {
        // base_path() = X:\laragon\www\dental-app-inch
        // dirname x1  = X:\laragon\www
        // dirname x2  = X:\laragon  ← correct
        return dirname(dirname(base_path()));
    }

    private function backupDir(): string {
        // Priorité 1 : variable OneDrive définie par Windows
        foreach (['OneDrive', 'OneDriveConsumer', 'OneDriveCommercial'] as $env) {
            $od = getenv($env);
            if ($od && is_dir($od)) {
                return rtrim($od, '\\/') . '\\backups\\dental-app';
            }
        }
        // Priorité 2 : USERPROFILE\OneDrive
        $profile = getenv('USERPROFILE');
        if ($profile && is_dir($profile . '\\OneDrive')) {
            return $profile . '\\OneDrive\\backups\\dental-app';
        }
        // Fallback : C:\backups\dental-app
        $drive = substr(base_path(), 0, 2);
        return $drive . '\\backups\\dental-app';
    }

    private function mysqldump(): string {
        $dirs = glob($this->laragonRoot() . '\\bin\\mysql\\*', GLOB_ONLYDIR);
        rsort($dirs);
        return ($dirs[0] ?? '') . '\\bin\\mysqldump.exe';
    }

    private function mysql(): string {
        $dirs = glob($this->laragonRoot() . '\\bin\\mysql\\*', GLOB_ONLYDIR);
        rsort($dirs);
        return ($dirs[0] ?? '') . '\\bin\\mysql.exe';
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/backup/list
    // ═══════════════════════════════════════════════════════════════
    public function list()
    {
        if (!is_dir($this->backupDir())) {
            return response()->json(['backups' => [], 'last_backup' => null, 'backup_dir' => $this->backupDir()]);
        }

        $entries = array_filter(scandir($this->backupDir()), function ($d) {
            return $d !== '.' && $d !== '..'
                && is_dir($this->backupDir() . '\\' . $d)
                && preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}$/', $d);
        });

        rsort($entries);

        $backups = array_map(function ($d) {
            $path    = $this->backupDir() . '\\' . $d;
            $sqlFile = $path . '\\database.sql';
            $zipFile = $path . '\\images.zip';
            return [
                'name'       => $d,
                'has_db'     => file_exists($sqlFile),
                'has_images' => file_exists($zipFile),
                'db_size'    => file_exists($sqlFile) ? filesize($sqlFile) : 0,
                'img_size'   => file_exists($zipFile) ? filesize($zipFile) : 0,
            ];
        }, array_values($entries));

        return response()->json([
            'backups'     => $backups,
            'last_backup' => count($backups) > 0 ? $backups[0]['name'] : null,
            'backup_dir'  => $this->backupDir(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/backup/run
    // ═══════════════════════════════════════════════════════════════
    public function run()
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['error' => 'La sauvegarde des images est temporairement indisponible. Contactez votre technicien.'], 500);
        }

        $timestamp = now()->format('Y-m-d_H-i');
        $dest      = $this->backupDir() . '\\' . $timestamp;

        if (!@mkdir($dest, 0755, true) && !is_dir($dest)) {
            return response()->json(['error' => 'Impossible de créer le dossier de backup.'], 500);
        }

        // ─── Export BDD ──────────────────────────────────────────
        $sqlFile     = $dest . '\\database.sql';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pass = $this->dbPass();
        $cmd = '"' . $this->mysqldump() . '" -u ' . $this->dbUser()
             . ($pass !== '' ? ' -p' . $pass : '')
             . ' --single-transaction --default-character-set=utf8mb4 --routines '
             . $this->dbName();

        $proc = proc_open($cmd, $descriptors, $pipes);
        fclose($pipes[0]);
        $sql    = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0 || strlen($sql) < 500) {
            rmdir($dest);
            return response()->json([
                'error'   => 'La sauvegarde de la base de données a échoué.',
                'details' => $errors,
            ], 500);
        }

        file_put_contents($sqlFile, $sql);

        // ─── Compresser les images ────────────────────────────────
        $zipFile    = $dest . '\\images.zip';
        $imgPath    = storage_path('app/public');
        $imgSizeKB  = 0;

        if (is_dir($imgPath)) {
            $zip = new ZipArchive();
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($imgPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relative = substr($file->getRealPath(), strlen($imgPath) + 1);
                    $zip->addFile($file->getRealPath(), $relative);
                }
            }
            $zip->close();
            $imgSizeKB = file_exists($zipFile) ? round(filesize($zipFile) / 1024) : 0;
        }

        // ─── Rotation adaptative selon taille du backup ───────────
        $backupSizeBytes = $this->dirSize($dest);
        $this->pruneBackups($backupSizeBytes);

        $backupCount = count(array_filter(scandir($this->backupDir()), fn($d) =>
            $d !== '.' && $d !== '..' && is_dir($this->backupDir() . '\\' . $d)
        ));

        return response()->json([
            'success'      => true,
            'name'         => $timestamp,
            'db_size_kb'   => round(filesize($sqlFile) / 1024),
            'img_size_kb'  => $imgSizeKB,
            'backup_count' => $backupCount,
            'max_backups'  => $this->maxBackups($backupSizeBytes),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/backup/restore
    // ═══════════════════════════════════════════════════════════════
    public function restore(Request $request)
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['error' => 'La sauvegarde des images est temporairement indisponible. Contactez votre technicien.'], 500);
        }

        $request->validate([
            'name' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}$/'],
        ]);

        $path = $this->backupDir() . '\\' . $request->name;
        if (!is_dir($path)) {
            return response()->json(['error' => 'Backup introuvable.'], 404);
        }

        // ─── Sauvegarder le token actuel avant écrasement de la BDD ──
        $currentToken = $request->user()->currentAccessToken();
        $savedToken   = DB::table('personal_access_tokens')->where('id', $currentToken->id)->first();

        // ─── Restaurer BDD ────────────────────────────────────────
        $sqlFile = $path . '\\database.sql';
        if (file_exists($sqlFile)) {
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $pass = $this->dbPass();
            $cmd  = '"' . $this->mysql() . '" -u ' . $this->dbUser()
                  . ($pass !== '' ? ' -p' . $pass : '')
                  . ' --default-character-set=utf8mb4 ' . $this->dbName();
            $proc = proc_open($cmd, $descriptors, $pipes);
            fwrite($pipes[0], file_get_contents($sqlFile));
            fclose($pipes[0]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);

            if ($code !== 0) {
                return response()->json(['error' => 'La restauration des données a échoué.', 'details' => $errors], 500);
            }
        }

        // ─── Réinsérer le token pour garder la session active ─────
        if ($savedToken) {
            DB::table('personal_access_tokens')->updateOrInsert(
                ['id' => $savedToken->id],
                (array) $savedToken
            );
        }

        // ─── Restaurer les images ─────────────────────────────────
        $zipFile = $path . '\\images.zip';
        if (file_exists($zipFile)) {
            $imgPath = storage_path('app/public');

            // Vérifier le ZIP avant de toucher aux images existantes
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                return response()->json(['error' => 'Fichier ZIP des images corrompu ou illisible.'], 500);
            }

            // Vider le contenu mais garder le dossier (la junction public/storage pointe dessus)
            if (is_dir($imgPath)) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($imgPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $f) {
                    $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath());
                }
            }
            if (!is_dir($imgPath)) { mkdir($imgPath, 0755, true); }
            $zip->extractTo($imgPath);
            $zip->close();
        }

        // Correction automatique des données corrompues (encodage CP850)
        Artisan::call('app:fix-encoding');

        return response()->json(['success' => true, 'restored' => $request->name]);
    }

    // ─── Nombre de backups à garder selon la taille ──────────────
    private function maxBackups(int $sizeBytes): int
    {
        $sizeMB = $sizeBytes / 1024 / 1024;
        if ($sizeMB < 50)  return 30;
        if ($sizeMB < 200) return 20;
        if ($sizeMB < 500) return 10;
        return 5;
    }

    // ─── Rotation : supprime les plus anciens selon palier ────────
    private function pruneBackups(int $backupSizeBytes): void
    {
        if (!is_dir($this->backupDir())) return;

        $keep = $this->maxBackups($backupSizeBytes);

        $dirs = array_values(array_filter(scandir($this->backupDir()), fn($d) =>
            $d !== '.' && $d !== '..' && is_dir($this->backupDir() . '\\' . $d)
        ));
        sort($dirs); // du plus ancien au plus récent

        $toDelete = array_slice($dirs, 0, max(0, count($dirs) - $keep));
        foreach ($toDelete as $d) {
            $this->deleteDir($this->backupDir() . '\\' . $d);
        }
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        if (!is_dir($path)) return 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $f) {
            $size += $f->getSize();
        }
        return $size;
    }

    private function deleteDir(string $path): void
    {
        if (!is_dir($path)) return;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath());
        }
        rmdir($path);
    }
}
