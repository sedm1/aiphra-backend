<?php

namespace Utils;

abstract class Cookies {
    public static function get(string $name): string {
        $value = $_COOKIE[$name] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    public static function set(string $name, string $value, int $ttlSeconds): void {
        if (headers_sent()) {
            return;
        }

        $expires = 0;
        if ($ttlSeconds > 0) {
            $expires = time() + $ttlSeconds;
        }

        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'domain' => parse_url(getenv('SITE_HOST'), PHP_URL_HOST),
            'secure' => self::isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function isSecureRequest(): bool {
        $https = $_SERVER['HTTPS'] ?? '';
        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (is_string($proto) && strtolower($proto) === 'https') {
            return true;
        }

        return false;
    }
}
