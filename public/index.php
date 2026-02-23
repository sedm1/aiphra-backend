<?php

use API\Types\Email;

require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

vd(123);

$api = new \Services\Users\Methods\Reg\Add();
$api->email = new Email('novikovn383@gmail.com');
$api->call();
