<?php

require __DIR__ . '/../bootstrap.php';

use Services\Users;
$code = req('code');
$email = req('email');
if (!$code || !$email) {
    http_response_code(403);

    exit();
}

$email = f_email($email);

Users\Mods\Reg::acceptEmail($email, $code);

//header('Location: /', true, 301);
//exit;
