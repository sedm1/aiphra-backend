<?php

namespace API\Types;

/**
 * Регулярное выражение
 *
 * Пустое значение разрешено
 *
 * Управляющие символы "/" в начале и в конце строки будут удалены
 *
 * @example /^year_(\d\d\d\d)$/
 */
class RegExp extends AbstractString {

	final protected function prepare(string $value): string {
		if ($value === '') return '';

		$value = trim($value, '/');

		return $value;
	}

	final protected function check(string $value): void {
		if ($value === '') return;

		$value = str_replace('~', '\~', $value);

		// unsanitize for check
		$value = str_replace('\\\\', '\\', $value);

		if (@preg_match("~$value~", '') === false) {
			self::throwTypeException(L('Error_regexp') . ': ' . $value);
		}
	}

}
