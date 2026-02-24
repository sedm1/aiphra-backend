<?php

namespace Controller\Reg;

use API\Types\Email;
use Controller\AbstractController;
use Services\Users\Methods\Reg\Add as RegAdd;

final class Add extends AbstractController {
    public function init(): void {
        $email = req('email');
        if (!$email) {
            throw new \Exception('Missing email', ERROR_CODE_REQUEST_REQUIRED);
        }

        $api = new RegAdd();
        $api->email = new Email($email);
        $api->call();
    }
}
