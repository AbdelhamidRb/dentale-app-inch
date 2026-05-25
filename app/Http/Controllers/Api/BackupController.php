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
    private string $backupDir  = 'C:\\backups\\dental-app';
    private string $mysqldump  = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
    private string $mysql      = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe';
    private string $dbName     = 'dental_db_inch';
    private string $dbUser     = 'root';
    private string $dbPass     = 'hamid2003';

    // ═══════════════════════════════════════════════════════════════
    // GET /api/backup/list
    // ═══════════════════════════════════════════════════════════════
    public function list()
    {
        if (!is_dir($this->backupDir)) {
            return response()->json(['backups' => [], 'last_backup' => null]);
        }

        $entries = array_filter(scandir($this->backupDir), function ($d) {
            return $d !== '.' && $d !== '..'
                && is_dir($this->backupDir . '\\' . $d)
                && preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}$/', $d);
        });

        rsort($entries);

        $backups = array_map(function ($d) {
            $path    = $this->backupDir . '\\' . $d;
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
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/backup/run
    // ═══════════════════════════════════════════════════════════════
    public function run()
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['error' => 'Extension PHP "zip" non activée. Activez-la dans php.ini puis redémarrez Apache dans Laragon.'], 500);
        }

        $timestamp = now()->format('Y-m-d_H-i');
        $dest      = $this->backupDir . '\\' . $timestamp;

        if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
            return response()->json(['error' => 'Impossible de créer le dossier de backup.'], 500);
        }

        // ─── Export BDD ──────────────────────────────────────────
        $sqlFile     = $dest . '\\database.sql';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $cmd = '"' . $this->mysqldump . '" -u ' . $this->dbUser
             . ' -p' . $this->dbPass
             . ' --single-transaction --default-character-set=utf8mb4 --routines '
             . $this->dbName;

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
                'error'   => 'Export MySQL échoué.',
                'details' => $errors,
            ], 500);
        }

        file_put_contents($sqlFile, $sql);

        // ─── Compresser les images ────────────────────────────────
        $zipFile    = $dest . '\\images.zip';
        $imgPath    = storage_path('app/public/patients');
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

        // ─── Supprimer anciens backups (garder 30) ───────────────
        $this->pruneBackups(30);

        return response()->json([
            'success'    => true,
            'name'       => $timestamp,
            'db_size_kb' => round(filesize($sqlFile) / 1024),
            'img_size_kb' => $imgSizeKB,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/backup/restore
    // ═══════════════════════════════════════════════════════════════
    public function restore(Request $request)
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['error' => 'Extension PHP "zip" non activée. Activez-la dans php.ini puis redémarrez Apache dans Laragon.'], 500);
        }

        $request->validate([
            'name' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}$/'],
        ]);

        $path = $this->backupDir . '\\' . $request->name;
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
            $cmd  = '"' . $this->mysql . '" -u ' . $this->dbUser
                  . ' -p' . $this->dbPass
                  . ' --default-character-set=utf8mb4 ' . $this->dbName;
            $proc = proc_open($cmd, $descriptors, $pipes);
            fwrite($pipes[0], file_get_contents($sqlFile));
            fclose($pipes[0]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);

            if ($code !== 0) {
                return response()->json(['error' => 'Restauration MySQL échouée.', 'details' => $errors], 500);
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
            $imgPath = storage_path('app/public/patients');
            if (is_dir($imgPath)) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($imgPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $f) {
                    $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath());
                }
                rmdir($imgPath);
            }
            mkdir($imgPath, 0755, true);
            $zip = new ZipArchive();
            $zip->open($zipFile);
            $zip->extractTo($imgPath);
            $zip->close();
        }

        // Correction automatique des données corrompues (encodage CP850)
        Artisan::call('app:fix-encoding');

        return response()->json(['success' => true, 'restored' => $request->name]);
    }

    // ─── Supprimer les backups les plus anciens ───────────────────
    private function pruneBackups(int $keep): void
    {
        if (!is_dir($this->backupDir)) return;

        $dirs = array_filter(scandir($this->backupDir), fn($d) =>
            $d !== '.' && $d !== '..' && is_dir($this->backupDir . '\\' . $d)
        );
        sort($dirs);

        $toDelete = array_slice($dirs, 0, max(0, count($dirs) - $keep));
        foreach ($toDelete as $d) {
            $this->deleteDir($this->backupDir . '\\' . $d);
        }
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
