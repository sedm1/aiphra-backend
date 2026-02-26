<?php

namespace Services\System\Mods;

use API\Types\Email;
use Services\System;

abstract class Actions {

    public const string T_ACTIONS = 'aiphra.actions';

    /**
     * Добавить событие
     *
     * @return string Код
     */
    public static function add(Email $email, System\Types\Action $action): string {
        $code = md5(rand(0, 1000)) . md5(rand(0, 1000));

        $set = [
            'email' => $email->value,
            'code' => $code,
            'action' => $action->value,
        ];
        dbh()->insert(System\Mods\Actions::T_ACTIONS)->set($set, true)->exec();

        return $code;
    }

    /**
     * Получить тип события
     */
    public static function get(string|Email $email, string $code): ?string {
        $formattedEmail = $email;
        if ($email instanceof Email) $formattedEmail = $email->value;

        $value = dbh()
            ->sel(['action'])
            ->from(System\Mods\Actions::T_ACTIONS)
            ->w([
                'email' => $formattedEmail,
                'code' => $code,
            ])
            ->fetchColumn();
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    public static function remove(string|Email $email, string $code): void {
        $formattedEmail = $email;
        if ($email instanceof Email) $formattedEmail = $email->value;

        dbh()
            ->del(System\Mods\Actions::T_ACTIONS)
            ->w([
                'email' => $formattedEmail,
                'code' => $code,
            ])
            ->exec();
    }
}
