<?php

namespace Services\Users\Mods;

use Models;
use Services\Users;
use Utils;

abstract class User {

    /**
     * Добавить пользователя в БД
     */
    public static function add(string $email, string $pass): int {
        dbh()
            ->insert(Models\User::T)
            ->set([
                'email' => $email,
                'pass_hash' => password_hash(Utils\System::getSecretHash($pass), PASSWORD_ARGON2ID),
            ])
            ->exec();

        return (int) dbh()->id();
    }

    /**
     * Получить пользователя по email
     */
    public static function getByEmail(string $email): false|array {
        return dbh()
            ->sel()
            ->from(Models\User::T)
            ->w(['email' => $email])
            ->fetch();
    }

    public static function genPass(int $length = 16): string {

        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';

        $all = $lower . $upper . $digits . $symbols;

        $password = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($password); $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }

    public static function auth(int $userId, ?string $redirect = null): int {
        $accessToken = Users\Mods\Tokens::issueAccessToken($userId);
        $refreshToken = Users\Mods\Tokens::issueRefreshToken($userId);

        Utils\Cookies::set('access_token', $accessToken, getenv('AUTH_ACCESS_TTL'));
        Utils\Cookies::set('refresh_token', $refreshToken, getenv('AUTH_REFRESH_TTL'));

        if (!headers_sent()) {
            header('X-Access-Token: ' . $accessToken);
            header('X-Refresh-Token: ' . $refreshToken);
            if ($redirect !== null && $redirect !== '') {
                header('Location: ' . $redirect, true, 302);
            }
        }

        return $userId;
    }
}
