<?php

namespace Services\Users\Methods\Reg;

use API;
use Exception;
use Models\User;
use Services\System;

/**
 * Первичная регистрация пользователя
 *
 * Создает ссылку на подтверждение регистрации и отправляет на почту
 */
final class Add extends API\Method\AbstractMethod {

    /**
     * Email для регистрации
     */
    public API\Types\Email $email;

    /**
     * @throws Exception
     */
    protected function exec(): string {
        $userExist = dbh()
            ->sel(1)
            ->from(User::T)
            ->w(['email' => $this->email->value])
            ->fetch();
        if ($userExist) throw new Exception('User already exist', ERROR_CODE_AUTH);

        $code = System\Mods\Actions::add($this->email, System\Types\Action::Email);

        //TODO: Заменить на отправку кода на почту
        return core()->getApiSiteHost() . '/income/login?' . http_build_query([
                'email' => $this->email->value,
                'code' => $code
            ]);
    }

}