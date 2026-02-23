<?php

namespace API\Types;

use TypeError;

/**
 * Класс для определения объектов, используемых в API
 *
 * Все параметры API типа Объект должны наследоваться от этого класса
 *
 * Все публичные параметры этого объекта должны определяться типами, так же как определяются параметры API метода
 *
 * Объект является readonly, поэтому во избежание путаницы данные должны устанавливаться исключительно в конструкторе
 *
 * Классы, описывающие объекты для параметров API, должны располагаться в: Services\{{ ServiceName }}\Types
 */
abstract readonly class AbstractObject {

//	/**
//	 * @param string $name Имя пользователя
//	 * @param int $age Возраст пользователя
//	 * @param Address $address Возраст пользователя
//	 */
//	public function __construct(
//		public string $name,
//		public int $age,
//		public Address $address,
//	) {
//		if (strlen($name) < 3) self::throwTypeException('name', 'very short');
//	}

	final protected static function throwTypeException(string $propertyName, ?string $messageAdditional = null): void {
		$message = L('Request_error_value') . ' ' . $propertyName;
		if (isset($messageAdditional)) $message .= " ($messageAdditional)";

		throw new TypeError($message);
	}

}
