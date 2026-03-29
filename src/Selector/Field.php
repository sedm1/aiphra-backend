<?php

namespace Selector;

use API\Types\AbstractTypedArray;
use Selector\Types\Operator;

/**
 * Облегчённые хелперы для формирования данных полей Selector.
 */
final class Field {

    /**
     * @return array{name:string, operator:string, values:array}
     */
    public static function genFilterData(string $alias, Operator $operator, array|AbstractTypedArray $values = []): array {
        if ($values instanceof AbstractTypedArray) {
            $values = $values->getStringValues();
        }

        return [
            'name' => $alias,
            'operator' => $operator->value,
            'values' => $values,
        ];
    }

    /**
     * @return array{name:string, direction:string, orderValues:?array, operator:?string, values:?array}
     */
    public static function genOrderData(
        string $alias,
        string $direction = 'ASC',
        ?array $orderValues = null,
        ?string $operator = null,
        array|AbstractTypedArray|null $values = null,
    ): array {
        if ($values instanceof AbstractTypedArray) {
            $values = $values->getStringValues();
        }

        return [
            'name' => $alias,
            'direction' => $direction,
            'orderValues' => $orderValues,
            'operator' => $operator,
            'values' => $values,
        ];
    }
}
