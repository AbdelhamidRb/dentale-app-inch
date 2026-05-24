<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LicenseGenerate extends Command
{
    protected $signature   = 'license:generate {email} {mac} {cabinet}';
    protected $description = 'Génère un fichier .lic signé pour un client';

    public function handle(): int
    {
        $email   = $this->argument('email');
        $mac     = strtolower(trim($this->argument('mac')));
        $cabinet = $this->argument('cabinet');

        $privateKeyPath = base_path('private.pem');
        if (!file_exists($privateKeyPath)) {
            $this->error('private.pem introuvable. Lancez d\'abord : php artisan license:keys');
            return 1;
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if (!$privateKey) {
            $this->error('Impossible de lire private.pem : ' . openssl_error_string());
            return 1;
        }

        $payload = json_encode([
            'email'      => $email,
            'mac'        => $mac,
            'cabinet'    => $cabinet,
            'issued_at'  => now()->toDateString(),
            'type'       => 'lifetime',
        ], JSON_UNESCAPED_UNICODE);

        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $licContent = base64_encode($payload) . '.' . base64_encode($signature);

        $filename = 'dental-app.lic';
        file_put_contents(base_path($filename), $licContent);

        $this->info("Licence générée : $filename");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Email',   $email],
                ['MAC',     $mac],
                ['Cabinet', $cabinet],
                ['Type',    'À vie (lifetime)'],
                ['Date',    now()->toDateString()],
            ]
        );
        $this->newLine();
        $this->line("Envoie ce fichier au client : " . base_path($filename));

        return 0;
    }
}
