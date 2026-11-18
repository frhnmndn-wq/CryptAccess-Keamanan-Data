<?php

declare(strict_types=1);

namespace CryptAccess\Core;

use PDO;

final class DB
{
    private static PDO $pdo;
    public static function connect(array $c): void
    {
        self::$pdo = new PDO($c['dsn'], $c['user'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false]);
    } public static function pdo(): PDO
    {
        return self::$pdo;
    } public static function one(string $q, array $p = []): ?array
    {
        $s = self::$pdo->prepare($q);
        $s->execute($p);
        return $s->fetch() ?: null;
    } public static function exec(string $q, array $p = []): int
    {
        $s = self::$pdo->prepare($q);
        $s->execute($p);
        return $s->rowCount();
    }
}
final class Csrf
{
    public static function token(): string
    {
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    } public static function check(): void
    {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(419);
            exit('Invalid request token.');
        }
    }
}
final class Log
{
    public static function add(?int $id, string $event, ?string $detail = null): void
    {
        DB::exec('INSERT INTO activity_logs(administrator_id,event_type,ip_address,detail) VALUES(?,?,?,?)', [$id,$event,substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),$detail]);
    }
}
final class Crypto
{
    public const PBKDF2_ITERATIONS = 600000;
    public static function derive(string $p, string $s, int $i = self::PBKDF2_ITERATIONS): string
    {
        return hash_pbkdf2('sha256', $p, $s, $i, 32, true);
    } public static function aes(string $plain, string $key): array
    {
        $nonce = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failure');
        }return[$cipher,$nonce,$tag];
    } public static function unseal(string $cipher, string $key, string $nonce, string $tag): string
    {
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($plain === false) {
            throw new \RuntimeException('Integrity verification failed');
        }return $plain;
    } public static function fingerprint(string $pem): string
    {
        $key = openssl_pkey_get_private($pem) ?: openssl_pkey_get_public($pem);
        if (!$key) {
            throw new \RuntimeException('Invalid RSA PEM');
        }return hash('sha256', openssl_pkey_get_details($key)['key']);
    } public static function pair(): array
    {
        $config = getenv('OPENSSL_CONF') ?: dirname(__DIR__).'/config/openssl.cnf';
        $key = openssl_pkey_new(['private_key_bits' => 2048,'private_key_type' => OPENSSL_KEYTYPE_RSA,'config' => $config]);
        if (!$key || !openssl_pkey_export($key, $private, null, ['config' => $config])) {
            throw new \RuntimeException('RSA key generation failed');
        }return[$private,openssl_pkey_get_details($key)['key']];
    }
    private static function cli(array $args, string $input, ?string $private = null): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ca_');
        $in = $tmp.'.in';
        $out = $tmp.'.out';
        file_put_contents($in, $input);
        if ($private !== null) {
            file_put_contents($tmp.'.pem', $private);
        }$cmd = 'openssl '.implode(' ', array_map('escapeshellarg', $args)).' -in '.escapeshellarg($in).' -out '.escapeshellarg($out).($private !== null ? ' -inkey '.escapeshellarg($tmp.'.pem') : '');
        exec($cmd.' 2>&1', $ignore, $code);
        $result = $code === 0 ? file_get_contents($out) : false;
        @unlink($in);
        @unlink($out);
        @unlink($tmp.'.pem');
        if ($result === false) {
            throw new \RuntimeException('OpenSSL operation failed');
        }return $result;
    } public static function wrap(string $dek, string $pub): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ca_pub_');
        file_put_contents($tmp, $pub);
        try {
            return self::cli(['pkeyutl','-encrypt','-pubin','-inkey',$tmp,'-pkeyopt','rsa_padding_mode:oaep','-pkeyopt','rsa_oaep_md:sha256','-pkeyopt','rsa_mgf1_md:sha256'], $dek);
        } finally {
            @unlink($tmp);
        }
    } public static function unwrap(string $data, string $priv): string
    {
        return self::cli(['pkeyutl','-decrypt','-pkeyopt','rsa_padding_mode:oaep','-pkeyopt','rsa_oaep_md:sha256','-pkeyopt','rsa_mgf1_md:sha256'], $data, $priv);
    } public static function sign(string $challenge, string $priv): string
    {
        return self::cli(['pkeyutl','-sign','-rawin','-digest','sha256','-pkeyopt','rsa_padding_mode:pss','-pkeyopt','rsa_pss_saltlen:digest'], $challenge, $priv);
    } public static function verify(string $challenge, string $sig, string $pub): bool
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ca_pub_');
        $in = $tmp.'.in';
        $sf = $tmp.'.sig';
        file_put_contents($tmp, $pub);
        file_put_contents($in, $challenge);
        file_put_contents($sf, $sig);
        exec('openssl pkeyutl -verify -pubin -inkey '.escapeshellarg($tmp).' -in '.escapeshellarg($in).' -sigfile '.escapeshellarg($sf).' -rawin -digest sha256 -pkeyopt rsa_padding_mode:pss -pkeyopt rsa_pss_saltlen:digest 2>&1', $ignore, $code);
        @unlink($tmp);
        @unlink($in);
        @unlink($sf);
        return $code === 0;
    }
}
final class Auth
{
    public static function user(): ?array
    {
        return isset($_SESSION['admin']) ? ['id' => (int)$_SESSION['admin'],'name' => (string)$_SESSION['name']] : null;
    } public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            header('Location: login.php');
            exit;
        }return $u;
    } public static function login(array $a): void
    {
        session_regenerate_id(true);
        $_SESSION['admin'] = $a['id'];
        $_SESSION['name'] = $a['display_name'];
    } public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }session_destroy();
    }
}
