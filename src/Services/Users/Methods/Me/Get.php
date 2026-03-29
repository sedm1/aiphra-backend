<?php

namespace Services\Users\Methods\Me;

use API;
use Models\User;
use Selector\Field;
use Selector\Selector;
use Selector\Types\Operator;

/**
 * Получение данных о текущем пользователе
 */
final class Get extends API\Method\AbstractWithModel {
    public const string MODEL = User::class;

    /**
     * @return array{
     *
     * }
     */
    protected function exec(): mixed {
        $selector = new Selector(self::MODEL);
        $selector->setFieldsSelect(['id', 'email']);
        $selector->addFieldFilter(Field::genFilterData('id', Operator::Equals, [user()->id]));

        return $selector->execFetch();
    }

}
