<?php

declare(strict_types=1);
session_name('cryptaccess');
session_start(['cookie_httponly' => true,'cookie_samesite' => 'Strict','cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
require __DIR__.'/Core.php';
use CryptAccess\Core\DB;

$cfgFile = __DIR__.'/../config/local.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('Configuration missing. Copy config/local.php.example to config/local.php.');
}
$cfg = require $cfgFile;
DB::connect($cfg);
