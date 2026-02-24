<?php

namespace Services\Users\Mods;

use Services\System;

abstract class Reg {

    /**
     * Подтверждение email
     */
    public static function acceptEmail(string $email, string $code): void {
        $action = System\Mods\Actions::get($email, $code);
        if (!$action) throw new \Exception('Событие не найдено', ERROR_CODE_NOT_FOUND);

        vd($action);
        $action = System\Types\Action::tryFrom($action);
        vd($action);
    }
}