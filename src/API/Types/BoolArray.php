<?php

namespace API\Types;

/**
 * Тип массива: boolean
 *
 * Принимает: 0|1|false|true
 *
 * @extends AbstractTypedArray<boolean>
 * @method bool[] getValues()
 * @method bool current()
 */
final class BoolArray extends AbstractTypedArray {

	protected const string ITEM_TYPE = 'bool';

	/**
	 * @param array<0|1|bool> $items
	 */
	public function __construct(array $items) {
		// входные данные не строгого типа
		foreach ($items as &$_item) {
			if (is_bool($_item)) continue;

			if ($_item == 0) $_item = false;
			if ($_item == 1) $_item = true;
		}

		parent::__construct($items);
	}

	protected function prepareValue(mixed $value): mixed {
		if (!is_bool($value)) {
			self::throwTypeException();
		}

		return $value;
	}

}
