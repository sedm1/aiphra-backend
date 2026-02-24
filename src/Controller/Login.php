<?php

namespace Controller;

use Services\Users;

final class Login extends AbstractController {
    public function init(): void {
        $code = req('code');
        $email = req('email');
        if (!$code || !$email) throw new \Exception('Missing code or email', ERROR_CODE_REQUEST_REQUIRED);

        $email = f_email($email);

        Users\Mods\Reg::acceptEmail($email, $code);
    }
}
