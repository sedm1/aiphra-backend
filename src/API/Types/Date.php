<?php

namespace API\Types;

/**
 * Дата
 *
 * @example 2000-01-01
 */
final class Date extends AbstractString {

	final protected function check(string $value): void {
		if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $value)) {
			self::throwTypeException('Available date format: \d\d\d\d-\d\d-\d\d');
		}
	}

}
