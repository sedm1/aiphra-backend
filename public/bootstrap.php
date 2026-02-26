<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Globals/Cors.php';

load_env(__DIR__ . '/../.env');
apply_cors();

header('Content-Type: application/json; charset=utf-8');
