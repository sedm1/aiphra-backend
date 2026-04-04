<?php

namespace Services\Users\Methods\Login;

use API;
use Exception;
use Services\Users\Methods\Reg\Mods\Users;
use Utils;

/**
 * Авторизация пользователя
 */
final class Get extends API\Method\AbstractMethod {

    /**
     * Email
     */
    public API\Types\Email $email;

    /**
     * Пароль
     */
    public string $password;

    /**
     * @throws Exception пользователь не найден
     * @throws Exception пароль пользователя неверен
     */
    protected function exec(): never {
        $user = Users::getByEmail($this->email->value);
        if (!$user) {
            throw new Exception('User not found', ERROR_CODE_AUTH);
        }

        $valid = password_verify(Utils\System::getSecretHash($this->password), $user['pass_hash']);
        if (!$valid) {
            throw new Exception('Invalid password', ERROR_CODE_AUTH);
        }

        $userId = intval($user['id']);
        Users::auth($userId, getenv('SITE_HOST'));
    }

}
