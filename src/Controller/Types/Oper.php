<?php

namespace Controller\Types;

/**
 * Методы API
 */
enum Oper: string {
    case Get = 'get';

    case Add = 'add';

    case Edit = 'edit';

    case Del = 'del';

}