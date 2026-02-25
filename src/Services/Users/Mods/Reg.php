<?php

namespace Services\Users\Mods;

use Services\System;

abstract class Reg {

    /**
     * Подтверждение email
     */
    public static function acceptEmail(string $email, string $code): void {
        $action = System\Types\Action::tryFrom(System\Mods\Actions::get($email, $code));
        if (!$action) throw new \Exception('Action hasn`t been found', ERROR_CODE_NOT_FOUND);


    }
}