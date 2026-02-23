<?php

namespace API\Types;

use JsonSerializable;
use Stringable;
use TypeError;

/**
 * Класс для определения типа строк в строгом формате
 */
abstract class AbstractString implements Stringable, JsonSerializable {

	public readonly string $value;

	final public function __construct(string $value) {
		$value = $this->prepare($value);

		$this->check($value);

		$this->value = $value;
	}

	final public function __toString(): string {
		return $this->value;
	}

	final public function jsonSerialize(): string {
		return $this->value;
	}

	/**
	 * Функция преобразования значения
	 */
	protected function prepare(string $value): string {
		return $value;
	}

	/**
	 * Функция проверки значения
	 *
	 * В случае ошибки должен вызывать `$this->throwTypeException($message)`
	 *
	 * @throws TypeError
	 */
	abstract protected function check(string $value): void;

	final public static function throwTypeException($message): never {
		throw new TypeError($message);
	}

}
