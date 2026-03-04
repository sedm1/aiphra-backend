<?php

namespace API\Types;

/**
 * Краткий URL не в punycode, может содержать UTF-символы:
 * - без схемы
 * - без "www." в домене
 * - без слешей на конце
 * - без "<" и ">"
 *
 * Будет проивзедено автоматическое приведение к формату
 *
 * Разрешен ввод пустой строки, для удаления значения url
 */
final class UrlShortUTF extends Url {

	protected const bool IS_PUNY = false;
	protected const bool CUT_SCHEMA_AND_WWW = true;

}
