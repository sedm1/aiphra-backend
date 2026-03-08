<?php

namespace Utils;

abstract class System {
    public static function checkEmail($email): bool {
        if (!preg_match("/^[-0-9a-z_\.\+]+@[-0-9a-z_^\.]+\.[a-z]{2,20}$/iu", $email)) return false;

        return true;
    }

    /**
     * Декдодировать зашифронный hash
     */
    public static function getSecret(string $hash): string {
        $raw = base64_decode($hash, true);

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $secret = getenv('APP_SECRET');

        $decoded = openssl_decrypt($ciphertext, 'AES-256-CBC', $secret, OPENSSL_RAW_DATA, $iv);

        return $decoded;
    }

    /**
     * Хешировать строку
     */
    public static function getSecretHash(string $value): string {
        $secret = (string)getenv('APP_SECRET');
        $iv = substr(hash_hmac('sha256', $value, $secret, true), 0, 16);
        $ciphertext = openssl_encrypt($value, 'AES-256-CBC', $secret, OPENSSL_RAW_DATA, $iv);
        if (!is_string($ciphertext)) {
            return '';
        }

        return base64_encode($iv . $ciphertext);
    }

}
