<?php

use Utils\Core;

if (!function_exists('core')) {
    function core(): Core {
        static $core = null;

        if ($core === null) {
            $core = new Core();
        }

        return $core;
    }
}
