<?php

namespace Services\Users\Mods;

use Exception;
use Services\System;
use Services\Users;

abstract class Reg {

    /**
     * Подтвердить аунтификацию пользователя
     */
    public static function acceptEmail(string $email, string $code): bool {
        $action = System\Mods\Actions::get($email, $code);
        if (!$action) {
            throw new Exception('Action was not found', ERROR_CODE_NOT_FOUND);
        }

        switch ($action['action']) {
            case System\Types\Action::Email->value:
                $user = Users\Mods\User::getByEmail($email);
                if ($user) {
                    throw new Exception('User already exist', ERROR_CODE_AUTH);
                }

                $pass = Users\Mods\User::genPass();
                $userId = Users\Mods\User::add($email, $pass);
                break;
            default:
                throw new Exception('Unsupported action', ERROR_CODE_REQUEST_DATA);
        }

        System\Mods\Actions::del($email, $code);
        Users\Mods\User::auth($userId, getenv('SITE_HOST'));

        return true;
    }
}
