<?php

namespace Utils\Core;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * Информация при выполнении
 *
 * Заполять, если стоит что-то сообщить не останавливая поток
 */
trait InfoTrait {
    public array $info = [];


}