<?php

namespace API\Method;

use API\Params\FiltersTrait;

/**
 * Реализация API метода изменения данных на основе модели.
 */
abstract class AbstractEdit extends AbstractWithModel {
    use FiltersTrait;
}
