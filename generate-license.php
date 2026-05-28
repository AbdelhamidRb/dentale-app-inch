<?php
/**
 * Générateur de licence dental-app
 * Usage : php generate-license.php <mac> [cabinet] [email]
 * Exemple : php generate-license.php "a4:bb:6d:12:34:56" "Cabinet Dr. Ahmed" "ahmed@example.com"
 */

if ($argc < 2) {
    echo "Usage: php generate-license.php <mac> [cabinet] [email]\n";
    echo "Exemple: php generate-license.php \"a4:bb:6d:12:34:56\" \"Cabinet Dr. Ahmed\"\n";
    exit(1);
}

$mac     = strtolower(str_replace('-', ':', trim($argv[1])));
$cabinet = $argv[2] ?? 'Cabinet Dentaire';
$email   = $argv[3] ?? 'hamidrherib@gmail.com';

$privateKeyPath = __DIR__ . '/private.pem';
if (!file_exists($privateKeyPath)) {
    echo "ERREUR : private.pem introuvable dans " . __DIR__ . "\n";
    exit(1);
}

$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
if (!$privateKey) {
    echo "ERREUR : Impossible de lire private.pem\n";
    exit(1);
}

$payload = json_encode([
    'email'     => $email,
    'mac'       => $mac,
    'cabinet'   => $cabinet,
    'issued_at' => date('Y-m-d'),
    'type'      => 'lifetime',
]);

openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

$license = base64_encode($payload) . '.' . base64_encode($signature);

$outputPath = __DIR__ . '/dental-app.lic';
file_put_contents($outputPath, $license);

echo "✓ Licence générée avec succès !\n";
echo "  MAC      : {$mac}\n";
echo "  Cabinet  : {$cabinet}\n";
echo "  Fichier  : {$outputPath}\n";
echo "\nCopier dental-app.lic à la racine de l'app sur le PC du dentiste.\n";
