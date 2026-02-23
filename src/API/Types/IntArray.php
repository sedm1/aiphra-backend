<?php

namespace API\Types;

/**
 * Тип массива: int
 *
 * Принимает: Любые целые числа и числа в строках
 *
 * @extends AbstractTypedArray<int>
 * @method int[] getValues()
 * @method int current()
 */
final class IntArray extends AbstractTypedArray {

	protected const string ITEM_TYPE = 'int';

	/**
	 * @param numeric[] $items
	 */
	public function __construct(array $items = []) {
		// входные данные не строгого типа
		foreach ($items as &$_item) {
			if (is_int($_item)) continue;

			if (is_numeric($_item)) {
				$_item = (int) $_item;
			}
		}

		parent::__construct($items);
	}

	protected function prepareValue(mixed $value): mixed {
		if (!is_int($value)) {
			self::throwTypeException();
		}

		return $value;
	}

}
