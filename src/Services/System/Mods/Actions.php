<?php

namespace Services\System\Mods;

use API\Types\Email;
use Exception;
use Services\System;

abstract class Actions {

    public const string T_ACTIONS = 'aiphra.actions';

    /**
     * Добавить событие
     *
     * @return string Код
     */
    public static function add(Email $email, System\Types\Action $action): string {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $expiresAt = $now->add(new \DateInterval('PT10M'))->format('YmdHis');
        $nonce = bin2hex(random_bytes(16));
        $signature = hash_hmac('sha256', $email->value . '|' . $action->value . '|' . $expiresAt . '|' . $nonce, getenv('APP_SECRET'));
        $code = $expiresAt . '.' . $nonce . '.' . $signature;
        $codeHash = hash_hmac('sha256', $code, getenv('APP_SECRET'));

        dbh()
            ->insert(self::T_ACTIONS)
            ->set([
                'email' => $email->value,
                'code' => $codeHash,
                'action' => $action->value,
            ], true)
            ->exec();

        return $code;
    }
}

