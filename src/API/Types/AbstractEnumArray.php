<?php

namespace API\Types;

use BackedEnum;

/**
 * Тип массива: Enum[]
 *
 * Для реализации списка, содержащего элементы типа enum, данный класс надо расширить с указанием enum класса в ITEM_TYPE
 */
abstract class AbstractEnumArray extends AbstractTypedArray {

	/**
	 * @var class-string<BackedEnum>
	 */
	protected const string ITEM_TYPE = self::ITEM_TYPE;

	protected const bool IS_ENUM = true;

	final public function __construct(array $items) {
		parent::__construct($items);
	}

	/**
	 * Безопасная для SQL строка, если Enum содержит простые строки
	 */
	final public function implode(): string {
		return implode(',', $this->getStringValues());
	}

	protected function prepareValue(mixed $value): mixed {
		if (is_a($value, static::ITEM_TYPE)) return $value;

		$value = static::ITEM_TYPE::tryFrom($value);

		if (!$value) self::throwTypeException();

		return $value;
	}

}
