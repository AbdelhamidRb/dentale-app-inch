<?php
$lic = trim(file_get_contents('C:\laragon\www\dental-app-inch\dental-app.lic'));
$parts = explode('.', $lic, 2);
if (count($parts) !== 2) { echo "ERREUR: Format licence invalide (pas de point separateur)\n"; exit; }
$payload = base64_decode($parts[0]);
$sig = base64_decode($parts[1]);
$pubFile = 'C:\laragon\www\dental-app-inch\storage\app\license.pub';
if (!file_exists($pubFile)) { echo "ERREUR: license.pub introuvable\n"; exit; }
$pub = openssl_pkey_get_public(file_get_contents($pubFile));
if (!$pub) { echo "ERREUR: Cle publique invalide\n"; exit; }
$ok = openssl_verify($payload, $sig, $pub, OPENSSL_ALGO_SHA256);
echo "Payload : " . $payload . "\n";
echo "Resultat: " . $ok . " (1=OK, 0=signature invalide, -1=erreur)\n";
