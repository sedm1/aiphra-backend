<?php

namespace API\Types;

/**
 * URL не в punycode, может содержать UTF-символы:
 * - без "<" и ">"
 *
 * Будет проивзедено автоматическое приведение к формату
 *
 * Разрешен ввод пустой строки, для удаления значения url
 */
final class UrlUTF extends Url {

	protected const bool IS_PUNY = false;
	protected const bool CUT_SCHEMA_AND_WWW = false;

}
