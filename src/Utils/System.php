<?php

namespace Utils;

abstract class System {
    public static function checkEmail($email): bool {
        if (!preg_match("/^[-0-9a-z_\.\+]+@[-0-9a-z_^\.]+\.[a-z]{2,20}$/iu", $email)) return false;

        return true;
    }

}