<?php

namespace API\Types;

use LogicException;

/**
 * Тип массива: AbstractObject[]
 *
 * Для реалзиции списка, содержащего объекты в строгом формате
 *
 * Данный класс надо расширить с указанием нужного расшерения класса AbstractObject в ITEM_TYPE
 *
 * @template ITEM_TYPE
 * @extends AbstractTypedArray<ITEM_TYPE>
 */
abstract class AbstractObjectArray extends AbstractTypedArray {

	/**
	 * @var class-string<AbstractObject>
	 */
	protected const string ITEM_TYPE = self::ITEM_TYPE;

	/**
	 * @param ITEM_TYPE[] $items
	 */
	final public function __construct(array $items = []) {
		if (!is_subclass_of(static::ITEM_TYPE, AbstractObject::class)) {
			throw new LogicException(static::class . '::ITEM_TYPE must be extended of: ' . AbstractObject::class, ERROR_CODE_INTERNAL_LOGIC);
		}

		parent::__construct($items);
	}

	final protected function prepareValue(mixed $value): mixed {
		$type = static::ITEM_TYPE;

		if ($value instanceof $type) return $value;

		// нельзя вставлять не подготовленные значения
		self::throwTypeException();
	}

}
