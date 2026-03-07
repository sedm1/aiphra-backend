<?php

use Utils\Core;
use Services\Users\Objects\User;

if (!function_exists('core')) {
    function core(): Core {
        static $core = null;

        if ($core === null) {
            $core = new Core();
        }

        return $core;
    }
}

if (!function_exists('user')) {
    function user(): User {
        static $user = null;

        if ($user === null) {
            $user = new User();
        }

        return $user;
    }
}
