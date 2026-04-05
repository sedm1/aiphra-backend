<?php

namespace Services\System\Mods;

use API\Types\Email;
use Exception;
use Services\System;

abstract class Actions {

    public final const string T_ACTIONS = 'aiphra.actions';

    /**
     * Добавить событие
     *
     * @return string Код
     */
    public static function add(Email $email, System\Types\Action $action): string {
        $code = md5(rand(0, 1000)) . md5(rand(0, 1000));

        dbh()
            ->insert(System\Mods\Actions::T_ACTIONS)
            ->set([
                'email' => $email->value,
                'code' => $code,
                'action' => $action->value,
            ], true)
            ->exec();

        return $code;
    }

    /**
     * Найти действие с подтверждением
     *
     * @return array{
     *     id: int,
     *     email: string,
     *     code: string,
     *     action: System\Types\Action
     * }|false
     */
    public static function get(string $email = '', string $code = ''): array|false {
        return dbh()
            ->sel()
            ->from(System\Mods\Actions::T_ACTIONS)
            ->w(['email' => $email, 'code' => $code])
            ->fetch();
    }

    public static function del(string $email, string $code): bool {
        return dbh()
            ->del(System\Mods\Actions::T_ACTIONS)
            ->w(['email' => $email, 'code' => $code])
            ->exec();
    }
}

