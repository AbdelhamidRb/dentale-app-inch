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
        return '"' . $this->laragonRoot() . '\\bin\\git\\bin\\git.exe"';
    }

    private function php(): string
    {
        $dirs = glob($this->laragonRoot() . '\\bin\\php\\php-8.*', GLOB_ONLYDIR);
        rsort($dirs);
        return '"' . ($dirs[0] ?? '') . '\\php.exe"';
    }

    private function run(string $cmd): array
    {
        $output = []; $code = 0;
        exec($cmd . ' 2>&1', $output, $code);
        return ['output' => implode("\n", $output), 'code' => $code];
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/update/status — version actuelle + vérifier si MAJ dispo
    // ═══════════════════════════════════════════════════════════════
    public function status()
    {
        $app = base_path();
        $git = $this->git();

        $current = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');

        // Fetch silencieux pour vérifier si update dispo
        shell_exec($git . ' -C "' . $app . '" fetch origin main --quiet 2>&1');
        $remote  = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short origin/main 2>&1') ?? '');

        return response()->json([
            'current'    => $current ?: '—',
            'latest'     => $remote  ?: '—',
            'up_to_date' => ($current && $remote && $current === $remote),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/update/run — sauvegarde + mise à jour complète
    // ═══════════════════════════════════════════════════════════════
    public function runUpdate()
    {
        set_time_limit(180);

        $app  = base_path();
        $git  = $this->git();
        $php  = $this->php();
        $log  = [];
        $errors = [];

        // ── 1. Sauvegarde BDD avant tout ────────────────────────────
        try {
            app(BackupController::class)->run();
            $log[] = '✓ Sauvegarde créée';
        } catch (\Throwable $e) {
            $log[]    = '⚠ Sauvegarde ignorée : ' . $e->getMessage();
            // Non bloquant — on continue quand même
        }

        // ── 2. Récupérer la version actuelle ────────────────────────
        $before = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');

        // ── 3. Git fetch + reset ─────────────────────────────────────
        $fetch = $this->run($git . ' -C "' . $app . '" fetch origin main --quiet');
        if ($fetch['code'] !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de contacter GitHub. Vérifiez la connexion internet.',
                'log'     => $log,
            ], 500);
        }
        $log[] = '✓ Téléchargement terminé';

        $reset = $this->run($git . ' -C "' . $app . '" reset --hard origin/main');
        if ($reset['code'] !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des fichiers.',
                'log'     => array_merge($log, [$reset['output']]),
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

        // ── 5. Vider les caches ──────────────────────────────────────
        $this->run($php . ' "' . $app . '\\artisan" route:clear');
        $this->run($php . ' "' . $app . '\\artisan" config:cache');
        $this->run($php . ' "' . $app . '\\artisan" view:cache');
        $this->run($php . ' "' . $app . '\\artisan" cache:clear');
        $log[] = '✓ Caches vidés';

        // ── 6. OPcache ───────────────────────────────────────────────
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $log[] = '✓ OPcache réinitialisé';
        }

        // ── 7. Version après mise à jour ─────────────────────────────
        $after = trim(shell_exec($git . ' -C "' . $app . '" rev-parse --short HEAD 2>&1') ?? '');

        $alreadyUpToDate = ($before === $after);

        return response()->json([
            'success'          => empty($errors),
            'already_up_to_date' => $alreadyUpToDate,
            'message'          => $alreadyUpToDate
                ? 'L\'application est déjà à jour.'
                : 'Mise à jour appliquée avec succès.',
            'version_before'   => $before,
            'version_after'    => $after,
            'log'              => $log,
            'errors'           => $errors,
        ]);
    }
}
