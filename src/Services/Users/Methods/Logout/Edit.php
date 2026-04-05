<?php

namespace Services\Users\Methods\Logout;

use API;
use Services\Users;
use Utils\Cookies;

/**
 * Выйти из аккаунта
 */
final class Edit extends API\Method\AbstractMethod {
    protected function exec(): bool {
        $refreshToken = Cookies::get('refresh_token');
        if ($refreshToken !== '') {
            $tokenHash = hash_hmac('sha256', $refreshToken, getenv('APP_SECRET'));

            dbh()
                ->del(Users\Mods\Tokens::T_REFRESH)
                ->w(['token_hash' => $tokenHash])
                ->exec();
        }

        Cookies::set('access_token', '', 1);
        Cookies::set('refresh_token', '', 1);
        user()->reset();

        if (!headers_sent()) {
            header('X-Access-Token:');
            header('X-Refresh-Token:');
        }

        return true;
    }

}
