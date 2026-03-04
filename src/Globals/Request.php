<?php

function throwRequestRequired(string $name, ?string $message = null): void {
	throw new Exception($name, $message);
}

function throwRequestType(string $name, ?string $message = null): void {
	throw new Exception($name, $message);
}

function throwRequestValue(string $name, ?string $message = null): void {
	throw new Exception($name, $message);
}

function sanitize($value, $quotes2entity = true) {
	if (is_null($value) || is_bool($value) || is_int($value) || is_float($value) || is_object($value)) return $value;

	if (is_array($value)) {
		foreach ($value as $i => $value_i) {
			$value[$i] = sanitize($value_i, $quotes2entity);
		}

		return $value;
	}

	if ($quotes2entity) {
		$value = preg_replace('~(<|&lt;)(\w|[!?\/])~i', '$1 $2', $value); // убрать открывание тэгов
		$value = str_replace("'", '&#39;', $value);
		$value = str_replace('"', '&quot;', $value);
	}

	return addslashes($value);
}

// получить данные из запроса (GET, POST)
function request($name, $default = '', $important_required = false, $sanitize = true, $sanitize_quotes2entity = true) {
	if (isset($_REQUEST[$name])) {
		$value = $_REQUEST[$name];
		if ($sanitize) $value = sanitize($value, $sanitize_quotes2entity);

		return $value;
	} elseif ($important_required) {
		throwRequestRequired($name);
	}

	return $default;
}

// получить данные из запроса (краткая запись)
function req($name, $default = '', $important_required = false, $sanitize = true, $sanitize_quotes2entity = true) {
	return request($name, $default, $important_required, $sanitize, $sanitize_quotes2entity);
}

// получить данные из запроса (с проверкой типа)
function r_string($name, $default = '', $important_required = false): ?string {
	$val = req($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (!is_string($val)) throwRequestType($name);

	return $val;
}

function r_int($name, $default = 0, $important_required = false): ?int {
	$val = req($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (!is_numeric($val)) throwRequestType($name);

	return (int) $val;
}

function r_float($name, $default = 0, $important_required = false): ?float {
	$val = req($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (!is_numeric($val)) throwRequestType($name);

	return (float) $val;
}

function r_bool($name, $default = false, $important_required = false): ?bool {
	$val = req($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if ($val == 0) $val = false;
	if ($val == 1) $val = true;

	if (!is_bool($val)) throwRequestType($name);

	return (bool) $val;
}

function r_date($name, $default = '2010-01-01', $important_required = false): ?string {
	$val = r_string($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $val)) throwRequestValue($name);

	return $val;
}

function r_datetime($name, $default = '2010-01-01 00:00:00', $important_required = false): ?string {
	$val = r_string($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (!preg_match('/^\d\d\d\d-\d\d-\d\d[ T]\d\d:\d\d:\d\d$/', $val)) throwRequestValue($name);

	return $val;
}

function r_url($name, $default = '', $important_required = false, $convertToPuny = true): ?string {
	$val = r_string($name, $default, $important_required, false, false);

	if (is_null($default) and is_null($val)) return $val;

	if ($val === '') return '';

	$val = trim($val);

	if (str_contains($val, ' ')) {
		throwRequestValue($name, L('Invalid_url') . ': ' . $val);
	}

	$val = preg_replace('~(^(?:https?://)?(?:www\.)?)|/+$~i', '', $val);
	$val = str_replace(['<', '>'], '', $val);

	if ($convertToPuny) {
		$val = Utils\Url::toPuny($val);
		$val = Utils\Url::hostToLowerCase($val);
	} else {
		$val = Utils\Url::fromPuny($val);
		$val = Utils\Url::hostToLowerCase($val);
	}

	$domain = preg_replace('~^https?://|/.*~i', '', $val);

    if (!Utils\Url::validDomain($domain)) throwRequestValue($name, L('PROJECTS_Incorrect_domain') . ': ' . $val);

	return $val;
}

function r_url_full($name, $default = '', $important_required = false, $convertToPuny = true): ?string {
	$val = r_string($name, $default, $important_required, false, false);

	if (is_null($default) and is_null($val)) return $val;

	if ($val === '') return $val;

	if ($convertToPuny) {
		$val = Utils\Url::toPuny($val);
		$val = Utils\Url::hostToLowerCase($val);
	} else {
		$val = Utils\Url::fromPuny($val);
		$val = Utils\Url::hostToLowerCase($val);
	}

	$domain = preg_replace('~^https?://|(:\d+)?/.*~i', '', $val);
	if (!Utils\Url::validDomain($domain)) throwRequestValue($name, L('PROJECTS_Incorrect_domain') . ': ' . $val);

	return $val;
}

function r_email($name, $default = '', $important_required = false): ?string {
	$val = r_string($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	$val = f_email($val);
	if (!Utils\System::checkEmail($val)) throwRequestValue('', L('Enter_valid_email_notice'));

	return $val;
}

// получить данные из запроса (с проверкой значения)
function r_exp($name, $default = '', $important_required = false, array|BackedEnum $expected = []) {
	$val = req($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	if (is_array($val)) throwRequestType($name);

	if (is_array($expected)) {
		if (!in_array($val, $expected)) throwRequestValue($name);
	} else {
		$ok = $expected::tryFrom($val);
		if (!$ok) throwRequestValue($name);
	}

	return $val;
}

function r_arr($name, $default = [], $important_required = false, $sanitize = true, $sanitize_quotes2entity = true): ?array {
	$val = req($name, $default, $important_required, $sanitize, $sanitize_quotes2entity);

	if (is_null($default) and is_null($val)) return $val;

	if (!is_array($val)) throwRequestType($name);

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - число)
// ключи массива - только числа
function r_arr_int($name, $default = [], $important_required = false): ?array {
	$val = r_arr($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as $index => $val_i) {
		if (!is_numeric($index)) throwRequestType($name);
		if (!is_numeric($val_i)) throwRequestType($name);

		$val[$index] = $val[$index] * 1;
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - bool)
// ключи массива - только числа
function r_arr_bool($name, $default = [], $important_required = false): ?array {
	$val = r_arr($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as $index => $val_i) {
		if ($val_i == 0) $val_i = false;
		if ($val_i == 1) $val_i = true;

		if (!is_bool($val_i)) throwRequestType($name);

		$val[$index] = $val_i;
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - дата)
function r_arr_date($name, $default = [], $important_required = false) {
	$val = r_arr($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as $val_i) {
		if (!is_string($val_i)) throwRequestType($name);

		if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $val_i)) throwRequestValue($name);
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - url)
function r_arr_url($name, $default = [], $important_required = false, $convertToPuny = true) {
	$val = r_arr($name, $default, $important_required, false, false);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as &$_val_i) {
		$_val_i = r_url('', $_val_i, false, $convertToPuny);
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - полного url)
function r_arr_url_full($name, $default = [], $important_required = false, $convertToPuny = true) {
	$val = r_arr($name, $default, $important_required, false, false);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as &$_val_i) {
		$_val_i = r_url_full('', $_val_i, false, $convertToPuny);
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой типа - url)
function r_arr_email($name, $default = [], $important_required = false) {
	$val = r_arr($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as &$_val_i) {
		$_val_i = r_email('', $_val_i);
	}

	return $val;
}

// получить данные из запроса в виде массива (с проверкой значения)
function r_arr_exp($name, $default = [], $important_required = false, $expected = []) {
	$val = r_arr($name, $default, $important_required);

	if (is_null($default) and is_null($val)) return $val;

	foreach ($val as $val_i) {
		if (is_array($val_i)) throwRequestType($name);
		if (!in_array($val_i, $expected)) throwRequestValue($name);
	}

	return $val;
}

function f_email(string $email): string {
	$email = trim($email);
	$email = strtolower($email);

	return $email;
}
