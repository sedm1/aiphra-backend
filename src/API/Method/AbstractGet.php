<?php

namespace API\Method;

use API\Params;
use Selector\Selector;

/**
 * Реализация API метода получения данных на основе модели.
 */
abstract class AbstractGet extends AbstractWithModel {

    use Params\FieldsTrait;
    use Params\OrdersTrait;
    use Params\FiltersTrait;
    use Params\LimitTrait;
    use Params\OffsetTrait;

    protected function genSelector(bool $forEdit = false): Selector {
        return parent::genSelector(false);
    }
}
