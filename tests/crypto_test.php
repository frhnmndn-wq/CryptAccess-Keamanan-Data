<?php

declare(strict_types=1);
require __DIR__.'/../app/Core.php';
use CryptAccess\Core\Crypto;

[$private, $public] = Crypto::pair();
$dek = random_bytes(32);
$wrapped = Crypto::wrap($dek, $public);
if (!hash_equals($dek, Crypto::unwrap($wrapped, $private))) {
    throw new RuntimeException('OAEP unwrap mismatch');
}
$challenge = random_bytes(32);
if (!Crypto::verify($challenge, Crypto::sign($challenge, $private), $public)) {
    throw new RuntimeException('PSS verification mismatch');
}
echo "Crypto integration test passed\n";
