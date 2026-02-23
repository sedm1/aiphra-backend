<?php

namespace API\Types;

/**
 * Тип массива: string[]
 *
 * @extends AbstractTypedArray<string>
 * @method string[] getValues()
 * @method string current()
 */
final class StringArray extends AbstractTypedArray {

	protected const string ITEM_TYPE = 'string';

	protected function prepareValue(mixed $value): mixed {
		if (!is_string($value)) {
			self::throwTypeException();
		}

		return $value;
	}

}
