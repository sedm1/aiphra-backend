<?php

namespace API\Types;

use Utils;

/**
 * Краткий URL в punycode, не может содержать UTF-символы:
 * - без схемы
 * - без "www." в домене
 * - без слешей на конце
 * - без "<" и ">"
 *
 * Будет произведено автоматическое приведение к формату
 *
 * Разрешен ввод пустой строки, для удаления значения url
 */
class Url extends AbstractString {

	protected const bool IS_PUNY = true;
	protected const bool CUT_SCHEMA_AND_WWW = false;

	final protected function prepare(string $value): string {
		if ($value === '') return '';

		$value = trim($value);

		if (static::CUT_SCHEMA_AND_WWW) {
			$value = preg_replace('~(^(?:\w+://)?(?:www\.)?)|/+$~i', '', $value);
		}

		$value = str_replace(['<', '>'], '', $value);

		if (static::IS_PUNY) {
			$value = Utils\Url::toPuny($value);
			$value = Utils\Url::hostToLowerCase($value);
		} else {
			$value = Utils\Url::fromPuny($value);
			$value = Utils\Url::hostToLowerCase($value);
		}

		return $value;
	}

	final protected function check(string $value): void {
		if ($value === '') return;

		if (str_contains($value, ' ')) {
			self::throwTypeException(L('Invalid_url') . ': ' . $value);
		}

		$domain = preg_replace('~^https?://|(:\d+)?/.*~i', '', $value);

        if (!Utils\Url::validDomain($domain)) {
            self::throwTypeException(L('PROJECTS_Incorrect_domain') . ': ' . $value);
        }
	}

}
