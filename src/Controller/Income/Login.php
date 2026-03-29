<?php

namespace Controller\Income;

use Controller;
use Exception;
use Services\Users;

/**
 * Страница авторизации
 */
final class Login extends Controller\AbstractController {

    public function init(): mixed {
        $code = req('code');
        $email = req('email') |>
                (fn($email) => urldecode($email)) |>
                (fn($email) => f_email($email)) |>
                (fn($email) => str_replace(' ', '+', $email));

        if (!$code || !$email) throw new Exception('Code and email are required', ERROR_CODE_REQUEST_DATA);

        Users\Methods\Reg\Mods\Reg::acceptEmail($email, $code);

        exit();
    }
}