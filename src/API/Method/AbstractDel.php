<?php

namespace API\Method;

use API\Params\FiltersTrait;

/**
 * Реализация API метода удаления данных на основе модели.
 */
abstract class AbstractDel extends AbstractWithModel {
    use FiltersTrait;
}
