<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class UpdateController extends Controller
{
    private function laragonRoot(): string
    {
        return dirname(dirname(base_path()));
    }

    private function git(): string
    {
        // -c credential.helper= desactive le gestionnaire de credentials GUI
        return '"' . $this->laragonRoot() . '\\bin\\git\\bin\\git.exe"'
            . ' -c credential.helper= -c core.askPass=';
    }

    private function php(): string
    {
        $dirs = glob($this->laragonRoot() . '\\bin\\php\\php-8.*', GLOB_ONLYDIR);
        rsort($dirs);
        return '"' . ($dirs[0] ?? '') . '\\php.exe"';
    }

    // Lit GITHUB_TOKEN directement depuis .env (fonctionne meme avec config:cache)
    private function githubToken(): string
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return '';
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), 'GITHUB_TOKEN=')) {
                return trim(substr(trim($line), strlen('GITHUB_TOKEN=')));
            }
        }
        return '';
    }

    private function repoUrl(): string
    {
        $token = $this->githubToken();
        return $token
            ? "https://{$token}@github.com/AbdelhamidRb/dentale-app-inch.git"
            : 'https://github.com/AbdelhamidRb/dentale-app-inch.git';
    }

    private function run(string $cmd): array
    {
        $output = []; $code = 0;
        exec($cmd . ' 2>&1', $output, $code);
        return ['output' => implode("\n", $output), 'code' => $code];
    }

    // Configure le remote origin avec le token puis restaure une URL propre
    private function setRemoteUrl(string $app, string $git, string $url): void
    {
        $this->run($git . ' -C "' . $app . '" remote set-url origin "' . $url . '"');
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/update/status
    // ═══════════════════════════════════════════════════════════════
    public function status()
    {
        $app = base_path();
        $git = $this->git();

        $current = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');

        // Configurer le remote avec token puis fetch
        $this->setRemoteUrl($app, $git, $this->repoUrl());
        shell_exec($git . ' -C "' . $app . '" fetch origin main --quiet 2>&1');
        $remote = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short origin/main 2>&1') ?? '');

        return response()->json([
            'current'    => $current ?: '—',
            'latest'     => $remote  ?: '—',
            'up_to_date' => ($current && $remote && $current === $remote),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/update/run
    // ═══════════════════════════════════════════════════════════════
    public function runUpdate()
    {
        set_time_limit(180);

        $app    = base_path();
        $git    = $this->git();
        $php    = $this->php();
        $log    = [];
        $errors = [];

        // ── 1. Sauvegarde avant tout ─────────────────────────────────
        try {
            app(BackupController::class)->run();
            $log[] = '✓ Sauvegarde créée';
        } catch (\Throwable $e) {
            $log[] = '⚠ Sauvegarde ignorée : ' . $e->getMessage();
        }

        // ── 2. Version actuelle ──────────────────────────────────────
        $before = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');

        // ── 3. Configurer remote + fetch + reset ─────────────────────
        $this->setRemoteUrl($app, $git, $this->repoUrl());

        $fetch = $this->run($git . ' -C "' . $app . '" fetch origin main --quiet');
        if ($fetch['code'] !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de télécharger la mise à jour. Vérifiez la connexion internet.',
                'log'     => $log,
                'errors'  => [$fetch['output']],
            ], 500);
        }
        $log[] = '✓ Téléchargement terminé';

        $reset = $this->run($git . ' -C "' . $app . '" reset --hard origin/main');
        if ($reset['code'] !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des fichiers.',
                'log'     => array_merge($log, [$reset['output']]),
                'errors'  => [],
            ], 500);
        }
        $log[] = '✓ Fichiers mis à jour';

        // ── 4. Migrations ────────────────────────────────────────────
        $migrate = $this->run($php . ' "' . $app . '\\artisan" migrate --force');
        if ($migrate['code'] !== 0) {
            $errors[] = 'Migrations : ' . $migrate['output'];
        } else {
            $log[] = '✓ Base de données migrée';
        }

        // ── 5. Caches ────────────────────────────────────────────────
        $this->run($php . ' "' . $app . '\\artisan" route:clear');
        $this->run($php . ' "' . $app . '\\artisan" config:cache');
        $this->run($php . ' "' . $app . '\\artisan" view:cache');
        $this->run($php . ' "' . $app . '\\artisan" cache:clear');
        $log[] = '✓ Caches vidés';

        // ── 6. Tâches planifiées obsolètes ───────────────────────────
        $this->run('schtasks /Delete /TN "DentalApp-Backup" /F');
        $log[] = '✓ Tâches système mises à jour';

        // ── 7. OPcache ───────────────────────────────────────────────
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $log[] = '✓ OPcache réinitialisé';
        }

        // ── 8. Version après ─────────────────────────────────────────
        $after = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');
        $alreadyUpToDate = ($before === $after);

        return response()->json([
            'success'            => empty($errors),
            'already_up_to_date' => $alreadyUpToDate,
            'message'            => $alreadyUpToDate
                ? "L'application est déjà à jour."
                : 'Mise à jour appliquée avec succès.',
            'version_before'     => $before,
            'version_after'      => $after,
            'log'                => $log,
            'errors'             => $errors,
        ]);
    }
}
