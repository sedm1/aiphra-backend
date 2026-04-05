<?php

namespace Services\Users\Mods;

use DateTimeImmutable;
use Exception;
use Models\User;

abstract class Tokens {
    public final const string T_REFRESH = 'aiphra.auth_refresh_tokens';

    public static function issueAccessToken(int $userId, ?string $email = null): string {
        // Access token is self-contained: id/email are signed and verified without DB lookup.
        if ($email === null || trim($email) === '') {
            $user = self::userById($userId);
            $email = $user['email'];
        }

        $ttl = intval(getenv('AUTH_ACCESS_TTL'));
        $expiresAt = time() + $ttl;
        $nonce = bin2hex(random_bytes(16));
        $emailEncoded = self::base64UrlEncode($email);

        $signature = hash_hmac('sha256', $userId . '|' . $expiresAt . '|' . $nonce . '|' . $emailEncoded, getenv('APP_SECRET'));

        $token = 'at2.' . $userId . '.' . $expiresAt . '.' . $nonce . '.' . $emailEncoded . '.' . $signature;

        return $token;
    }

    public static function issueRefreshToken(int $userId): string {
        $token = 'rt1_' . bin2hex(random_bytes(32));
        $tokenHash = hash_hmac('sha256', $token, getenv('APP_SECRET'));

        $ttl = intval(getenv('AUTH_REFRESH_TTL'));
        $expiresAt = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $expiresAt = $expiresAt->add(new \DateInterval('PT' . $ttl . 'S'));
        $expiresAtString = $expiresAt->format('Y-m-d H:i:s');

        dbh()
            ->insert(self::T_REFRESH)
            ->set([
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAtString,
            ], true)
            ->exec();

        return $token;
    }

    public static function refreshTokens(string $refreshToken): array {
        if ($refreshToken === '') {
            throw new Exception('Refresh token is required', ERROR_CODE_AUTH);
        }

        // Single active refresh token per user: each rotation replaces token_hash.
        $tokenHash = hash_hmac('sha256', $refreshToken, getenv('APP_SECRET'));
        $db = dbh();

        try {
            $db->beginTransaction();

            $row = $db
                ->sel(['id', 'user_id', 'expires_at'])
                ->from(self::T_REFRESH)
                ->w(['token_hash' => $tokenHash])
                ->fetch();

            if (!is_array($row)) {
                throw new Exception('Invalid refresh token', ERROR_CODE_AUTH);
            }

            $expiresTs = strtotime($row['expires_at']);
            if ($expiresTs <= time()) {
                throw new Exception('Refresh token expired', ERROR_CODE_AUTH);
            }

            $user = self::userById($row['user_id']);
            $accessToken = self::issueAccessToken($row['user_id'], $user['email']);
            $nextRefreshToken = self::issueRefreshToken($row['user_id']);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->dbh->inTransaction()) {
                $db->dbh->rollBack();
            }

            throw $e;
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $nextRefreshToken,
            'user_id' => $user['id'],
            'email' => $user['email'],
        ];
    }

    public static function resolveAccessToken(string $accessToken): ?array {
        $parts = explode('.', $accessToken, 6);
        if (count($parts) !== 6 || $parts[0] !== 'at2') {
            return null;
        }

        $userId = $parts[1];
        $expiresAt = $parts[2];
        $nonce = $parts[3];
        $emailEncoded = $parts[4];
        $signature = $parts[5];

        if ($userId === '' || $expiresAt === '' || $nonce === '' || $emailEncoded === '' || $signature === '') {
            return null;
        }

        if (!is_numeric($expiresAt) || intval($expiresAt) <= time()) {
            return null;
        }

        $expected = hash_hmac('sha256', $userId . '|' . $expiresAt . '|' . $nonce . '|' . $emailEncoded, getenv('APP_SECRET'));
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        if (!is_numeric($userId)) {
            return null;
        }

        $email = self::base64UrlDecode($emailEncoded);
        if ($email === null || $email === '') {
            return null;
        }

        return [
            'id' => $userId,
            'email' => $email,
        ];
    }

    private static function userById(int|string $userId): array {
        $row = dbh()
            ->sel(['id', 'email'])
            ->from(User::T)
            ->w(['id' => $userId])
            ->fetch();

        if (!is_array($row)) {
            throw new Exception('User not found', ERROR_CODE_AUTH);
        }

        return $row;
    }

    private static function base64UrlEncode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string {
        $padded = $value;
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
        if (!is_string($decoded)) {
            return null;
        }

        return $decoded;
    }
}
