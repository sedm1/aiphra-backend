<?php

use DB\PDO\Mysql;

/**
 * MySql подключение к БД
 */

function dbh(): Mysql {
    $config = [
        'host' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['DB_MYSQL_PORT'] ?? 3306),
        'dbname' => $_ENV['DB_MYSQL_NAME'] ?? '',
        'user' => $_ENV['DB_MYSQL_USER'] ?? '',
        'pass' => $_ENV['DB_MYSQL_PASS'] ?? '',
        'type' => $_ENV['DB_MYSQL_TYPE'] ?? 'mysql',
        'charset' => $_ENV['DB_MYSQL_CHARSET'] ?? 'utf8mb4',
    ];

    vd($config);

    return new Mysql($GLOBALS['dbh_mysql_site'], $config);
}
