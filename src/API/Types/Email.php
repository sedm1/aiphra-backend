<?php

namespace API\Types;

use Utils;

/**
 * E-mail
 *
 * @example name@example.com
 */
final class Email extends AbstractString {

    final protected function check(string $value): void {
        if (!Utils\System::checkEmail($value)) {
            self::throwTypeException('Введите корректный Email');
        }
    }

}
