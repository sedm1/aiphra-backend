<?php

/**
 * `var_dump` с дополнительными возможностями
 */
function vd(mixed $var, bool $exit = false): void {
    var_dump($var);

    if ($exit) exit();
}