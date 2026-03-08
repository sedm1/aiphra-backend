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
    protected function exec(): int {
        $userExist = dbh()
            ->sel(1)
            ->from(User::T)
            ->w(['email' => $this->email->value])
            ->fetch();
        if ($userExist) throw new Exception('User already exist', ERROR_CODE_AUTH);

        $code = System\Mods\Actions::add($this->email, System\Types\Action::Email);

        $link = core()->getApiSiteHost() . '/income/login?' . http_build_query([
                'email' => $this->email->value,
                'code' => $code
            ]);

        vd($link);
        return 123;

        // TODO: Подключить
//        return core()->send_mail(
//            $this->email->value,
//            '',
//            [
//                'link' => $link,
//                'code' => $code,
//            ],
//            'Регистрация'
//        );
    }

}