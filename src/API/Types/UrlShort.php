<?php

namespace API\Types;

/**
 * Краткий URL в punycode, не может содержать UTF-символы:
 * - без схемы
 * - без "www." в домене
 * - без слешей на конце
 * - без "<" и ">"
 *
 * Будет проивзедено автоматическое приведение к формату
 *
 * Разрешен ввод пустой строки, для удаления значения url
 */
final class UrlShort extends Url {

	protected const bool IS_PUNY = true;
	protected const bool CUT_SCHEMA_AND_WWW = true;

}
