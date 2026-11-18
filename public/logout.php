<?php

require __DIR__.'/../app/bootstrap.php';
use CryptAccess\Core\{Auth,Csrf,Log};

Csrf::check();
if ($u = Auth::user()) {
    Log::add($u['id'], 'logout');
} Auth::logout();
header('Location: index.php');
