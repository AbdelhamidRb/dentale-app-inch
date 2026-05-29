<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    private string $appRoot;
    private string $repoOwner = 'AbdelhamidRb';
    private string $repoName  = 'dentale-app-inch';
    private string $mysqldump = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
    private string $mysql     = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe';

    public function __construct()
    {
        $this->appRoot = base_path();
    }

    // ── GET /api/update/check ─────────────────────────────────────
    public function check()
    {
        $local  = $this->localVersion();
        $latest = Cache::remember('github_latest_version', 86400, fn() => $this->fetchLatestVersion());

        $available = $latest
            && $latest !== $local
            && version_compare(ltrim($latest, 'v'), ltrim($local, 'v'), '>');

        return response()->json([
            'local'     => $local,
            'latest'    => $latest ?? $local,
            'available' => $available,
        ]);
    }

    // ── POST /api/update/run ──────────────────────────────────────
    public function run()
    {
        $localVersion = $this->localVersion();
        $devEmail     = env('DEV_EMAIL', 'hamidrherib@gmail.com');
        $cabinet      = \App\Models\Setting::get('cabinet_name', config('app.name', 'Cabinet Dentaire'));

        // 1. Vérifier internet
        try {
            Http::timeout(5)->get('https://github.com');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Pas de connexion internet.'], 503);
        }

        // 2. Récupérer la nouvelle version
        Cache::forget('github_latest_version');
        $newVersion = $this->fetchLatestVersion();
        if (!$newVersion) {
            return response()->json(['error' => 'Impossible de contacter GitHub.'], 503);
        }

        // 3. Sauvegarder le commit actuel pour rollback
        $gitPath       = $this->findGit();
        $currentCommit = trim(shell_exec('"' . $gitPath . '" -C "' . $this->appRoot . '" rev-parse HEAD 2>&1') ?? '');

        // 4. Backup BDD automatique
        $backupResult = $this->backupDatabase();
        if (!$backupResult['success']) {
            return response()->json(['error' => 'Backup échoué avant mise à jour : ' . $backupResult['error']], 500);
        }
        $backupFile = $backupResult['file'];

        // 5. Fichier lock — détecte une mise à jour interrompue
        file_put_contents(base_path('.update_lock'), json_encode([
            'commit'  => $currentCommit,
            'backup'  => $backupFile,
            'version' => $localVersion,
            'started' => now()->toISOString(),
        ]));

        // 6. git fetch + reset --hard (évite les conflits de fichiers locaux)
        shell_exec('"' . $gitPath . '" -C "' . $this->appRoot . '" fetch origin main 2>&1');
        $pullOutput = shell_exec('"' . $gitPath . '" -C "' . $this->appRoot . '" reset --hard origin/main 2>&1') ?? '';

        if (
            str_contains(strtolower($pullOutput), 'error') ||
            str_contains(strtolower($pullOutput), 'fatal')
        ) {
            $this->rollback($currentCommit, $backupFile);
            $this->notify($devEmail, $cabinet, $localVersion, $newVersion, false, 'git reset --hard échoué : ' . $pullOutput);
            return response()->json(['error' => 'Mise à jour échouée. Votre application a été restaurée automatiquement.', 'details' => $pullOutput], 500);
        }

        // 7. php artisan migrate --force
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            $this->rollback($currentCommit, $backupFile);
            $this->notify($devEmail, $cabinet, $localVersion, $newVersion, false, 'Migration échouée : ' . $e->getMessage());
            return response()->json(['error' => 'Migration échouée. Votre application a été restaurée automatiquement.', 'details' => $e->getMessage()], 500);
        }

        // 8. Cache clear
        Artisan::call('cache:clear');
        Cache::forget('github_latest_version');

        // 9. Supprimer lock
        @unlink(base_path('.update_lock'));

        // 10. Notification succès
        $this->notify($devEmail, $cabinet, $localVersion, $newVersion, true, null);

        return response()->json([
            'success'        => true,
            'version_before' => $localVersion,
            'version_after'  => $newVersion,
        ]);
    }

    // ── GET /api/update/check-lock ────────────────────────────────
    // Appelé au démarrage de l'app — détecte une MAJ interrompue
    public function checkLock()
    {
        $lockFile = base_path('.update_lock');

        if (!file_exists($lockFile)) {
            return response()->json(['interrupted' => false]);
        }

        $lock = json_decode(file_get_contents($lockFile), true) ?? [];
        $this->rollback($lock['commit'] ?? '', $lock['backup'] ?? '');

        Log::warning('UpdateController: mise à jour interrompue détectée, rollback effectué', $lock);

        return response()->json([
            'interrupted'   => true,
            'rolled_back'   => true,
            'version'       => $lock['version'] ?? 'inconnue',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function localVersion(): string
    {
        $file = base_path('version.txt');
        return file_exists($file) ? trim(file_get_contents($file)) : 'v1.0.0';
    }

    private function fetchLatestVersion(): ?string
    {
        try {
            $headers = [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'DentalApp',
            ];
            $token = env('GITHUB_TOKEN');
            if ($token) {
                $headers['Authorization'] = 'token ' . $token;
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases/latest");

            if ($response->successful()) {
                return $response->json('tag_name');
            }
        } catch (\Exception $e) {
            Log::warning('UpdateController: impossible de vérifier la version GitHub', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function findGit(): string
    {
        $paths = [
            'C:\\laragon\\bin\\git\\bin\\git.exe',
            'C:\\Program Files\\Git\\bin\\git.exe',
            'C:\\Program Files (x86)\\Git\\bin\\git.exe',
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) return $path;
        }
        return 'git';
    }

    private function backupDatabase(): array
    {
        $backupDir  = 'C:\\backups\\dental-app\\_pre-update';
        $timestamp  = now()->format('Y-m-d_H-i-s');
        $backupFile = $backupDir . '\\' . $timestamp . '.sql';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $db   = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $cmd = '"' . $this->mysqldump . '" -u ' . $user
            . ($pass !== '' ? ' -p' . $pass : '')
            . ' --single-transaction --default-character-set=utf8mb4 ' . $db;

        $sql = shell_exec($cmd . ' 2>nul');

        if (!$sql || strlen($sql) < 500) {
            return ['success' => false, 'error' => 'mysqldump a échoué ou base vide'];
        }

        file_put_contents($backupFile, $sql);
        return ['success' => true, 'file' => $backupFile];
    }

    private function rollback(string $commit, string $backupFile): void
    {
        // Rollback code git
        if ($commit) {
            $gitPath = $this->findGit();
            shell_exec('"' . $gitPath . '" -C "' . $this->appRoot . '" reset --hard ' . $commit . ' 2>&1');
        }

        // Rollback BDD
        if ($backupFile && file_exists($backupFile)) {
            $db   = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $cmd         = '"' . $this->mysql . '" -u ' . $user . ($pass !== '' ? ' -p' . $pass : '') . ' ' . $db;
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc        = proc_open($cmd, $descriptors, $pipes);

            if (is_resource($proc)) {
                fwrite($pipes[0], file_get_contents($backupFile));
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
            }
        }

        @unlink(base_path('.update_lock'));
    }

    private function notify(string $to, string $cabinet, string $fromV, string $toV, bool $success, ?string $error): void
    {
        $date    = now()->format('d/m/Y H:i');
        $subject = $success
            ? "[DentalApp] MAJ reussie - $cabinet ($fromV -> $toV)"
            : "[DentalApp] MAJ echouee - $cabinet (reste $fromV)";

        $body = $success
            ? "Cabinet  : $cabinet\nVersion  : $fromV -> $toV\nDate     : $date\nStatut   : Succes"
            : "Cabinet  : $cabinet\nVersion  : $fromV (inchangee)\nDate     : $date\nErreur   : $error\nRollback : Effectue automatiquement";

        try {
            mail($to, $subject, $body);
        } catch (\Exception $e) {
            Log::info('[UpdateController] notification update', ['subject' => $subject, 'body' => $body]);
        }
    }
}
