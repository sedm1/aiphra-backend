<?php

namespace API\Types;

use LogicException;

/**
 * Тип массива: AbstractString[]
 *
 * Для реалзиции списка, содержащего строки в строгом формате
 *
 * Данный класс надо расширить с указанием нужного расшерения класса AbstractString в ITEM_TYPE
 *
 * @template ITEM_TYPE
 * @extends AbstractTypedArray<ITEM_TYPE>
 */
abstract class AbstractStringArray extends AbstractTypedArray {

	/**
	 * @var class-string<AbstractString>
	 */
	protected const string ITEM_TYPE = self::ITEM_TYPE;

	/**
	 * @param ITEM_TYPE[]|string[] $items
	 */
	final public function __construct(array $items) {
		if (!is_subclass_of(static::ITEM_TYPE, AbstractString::class)) {
			throw new LogicException(static::class . '::ITEM_TYPE must be extended of: ' . AbstractString::class, ERROR_CODE_INTERNAL_LOGIC);
		}

		parent::__construct($items);
	}

	final protected function prepareValue(mixed $value): mixed {
		$type = static::ITEM_TYPE;

		if ($value instanceof $type) return $value;

		if (!is_string($value)) {
			$type::throwTypeException(L('Request_error_type'));
		}

		return new $type($value);
	}

}
