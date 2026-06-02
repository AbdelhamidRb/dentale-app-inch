<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.license_skip', false)) {
            return $next($request);
        }

        // Cache 24h : getmac + verification RSA ne changent jamais en cours d'utilisation
        $result = cache()->remember('license_check', 86400, fn() => $this->verify());

        if ($result['valid']) {
            return $next($request);
        }

        return response()->json([
            'error'   => 'license_invalid',
            'reason'  => $result['reason'],
            'mac'     => $this->getMac(),
        ], 403);
    }

    private function verify(): array
    {
        $licPath = base_path('dental-app.lic');
        $pubPath = storage_path('app/license.pub');

        if (!file_exists($licPath)) {
            return ['valid' => false, 'reason' => 'Fichier dental-app.lic introuvable.'];
        }

        if (!file_exists($pubPath)) {
            return ['valid' => false, 'reason' => 'Clé publique introuvable.'];
        }

        $content = trim(file_get_contents($licPath));
        $parts   = explode('.', $content, 2);

        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Format de licence invalide.'];
        }

        $payload   = base64_decode($parts[0]);
        $signature = base64_decode($parts[1]);
        $publicKey = openssl_pkey_get_public(file_get_contents($pubPath));

        if (!$publicKey) {
            return ['valid' => false, 'reason' => 'Clé publique corrompue.'];
        }

        $ok = openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return ['valid' => false, 'reason' => 'Signature invalide. Licence falsifiée ou corrompue.'];
        }

        $data = json_decode($payload, true);
        if (!$data) {
            return ['valid' => false, 'reason' => 'Données de licence illisibles.'];
        }

        // Vérifier le MAC
        $currentMac = $this->getMac();
        if ($currentMac && strtolower($data['mac']) !== strtolower($currentMac)) {
            return ['valid' => false, 'reason' => 'Cette licence appartient à un autre PC.'];
        }

        // Vérifier l'expiration
        if (!empty($data['expires_at']) && now()->gt($data['expires_at'])) {
            return ['valid' => false, 'reason' => 'Licence expirée le ' . $data['expires_at'] . '. Contactez hamidrherib@gmail.com pour renouveler.'];
        }

        return ['valid' => true, 'reason' => null];
    }

    public function getMac(): string
    {
        return cache()->remember('app_mac_address', 86400, function () {
            exec('getmac /fo csv /nh 2>nul', $lines);
            foreach ($lines as $line) {
                if (preg_match('/"([0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2})"/', $line, $m)) {
                    return strtolower(str_replace('-', ':', $m[1]));
                }
            }
            return '';
        });
    }
}
