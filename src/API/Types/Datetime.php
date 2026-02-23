<?php

namespace API\Types;

/**
 * Дата с временем
 *
 * @example 2000-01-01 00:00:00
 */
final class Datetime extends AbstractString {

	final protected function check(string $value): void {
		if (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $value)) {
			self::throwTypeException('Available date format: \d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d');
		}
	}

}
