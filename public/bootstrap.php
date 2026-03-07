<?php

require __DIR__ . '/../vendor/autoload.php';

$lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) return;

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $pos = strpos($line, '=');
    if ($pos === false) {
        continue;
    }
    $key = trim(substr($line, 0, $pos));
    $value = trim(substr($line, $pos + 1));
    $value = trim($value, "\"'");

    if ($key === '' || getenv($key) !== false) {
        continue;
    }
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
}

header('Content-Type: application/json; charset=utf-8');
