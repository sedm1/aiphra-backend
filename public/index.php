<?php

require __DIR__ . '/../vendor/autoload.php';

load_env(__DIR__ . '/../.env');

header('Content-Type: application/json; charset=utf-8');

$t = microtime(true);
$db = dbh();                  // время подключения
vd(['connect' => microtime(true) - $t]);

$t = microtime(true);
$db = dbh();                  // время подключения
vd(['connect' => microtime(true) - $t]);

