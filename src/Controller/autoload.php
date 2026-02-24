<?php

define('APP_ROOT', realpath(__DIR__ . '/../..'));

require APP_ROOT . '/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'Controller\\';
    $prefixLen = strlen($prefix);
    if (strncmp($class, $prefix, $prefixLen) !== 0) {
        return;
    }

    $relative = substr($class, $prefixLen);
    $relativePath = str_replace('\\', '/', $relative);
    $file = APP_ROOT . '/src/Controller/' . $relativePath . '.php';

    if (is_file($file)) {
        require $file;
    }
});
