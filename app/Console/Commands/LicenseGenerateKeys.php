<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LicenseGenerateKeys extends Command
{
    protected $signature   = 'license:keys';
    protected $description = 'Génère la paire de clés RSA (privée + publique) pour le système de licence';

    public function handle(): int
    {
        $privateKeyPath = base_path('private.pem');
        $publicKeyPath  = storage_path('app/license.pub');

        if (file_exists($privateKeyPath)) {
            if (!$this->confirm('private.pem existe déjà. Écraser ?', false)) {
                $this->info('Annulé.');
                return 0;
            }
        }

        $key = openssl_pkey_new([
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (!$key) {
            $this->error('Erreur OpenSSL : ' . openssl_error_string());
            return 1;
        }

        // Clé privée
        openssl_pkey_export($key, $privatePem);
        file_put_contents($privateKeyPath, $privatePem);

        // Clé publique
        $details = openssl_pkey_get_details($key);
        file_put_contents($publicKeyPath, $details['key']);

        $this->info('Clés générées :');
        $this->line("  Privée : $privateKeyPath  ← GARDER SECRET, ne jamais partager");
        $this->line("  Publique : $publicKeyPath  ← incluse dans l'app");
        $this->newLine();
        $this->warn('⚠  Ajoutez private.pem dans .gitignore !');

        return 0;
    }
}
