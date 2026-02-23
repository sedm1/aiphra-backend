<?php

namespace API\Types;

use ArrayAccess;
use BackedEnum;
use Countable;
use Iterator;
use JsonSerializable;
use Override;
use RangeException;
use TypeError;

/**
 * Класс для определения типизированного списка
 *
 * Принимает сырые значения или готовые реализации типа self::ITEM_TYPE
 *
 * Хранить готовые реалзиации типа self::ITEM_TYPE
 *
 * Для реалзиции списка, содержашего элементы нужного типа, данный класс надо расширить с указанием типа или класса в ITEM_TYPE.
 *
 * @template ITEM_TYPE Тип элементов массива
 */
abstract class AbstractTypedArray implements ArrayAccess, Iterator, Countable, JsonSerializable {

	/**
	 * @var ITEM_TYPE[]
	 */
	protected array $items = [];

	private int $position = 0;

	protected const string ITEM_TYPE = self::ITEM_TYPE;
	protected const bool IS_ENUM = false;

	final public function jsonSerialize(): array {
		return $this->items;
	}

	/**
	 * @param array|ITEM_TYPE[] $items
	 */
	public function __construct(array $items = []) {
		foreach ($items as $index => $item) {
			$this[$index] = $item;
		}
	}

	public static function getItemType(): string {
		return static::ITEM_TYPE;
	}

	final public static function checkIndex(mixed $index): void {
		if (!is_int($index)) {
			throw new TypeError('Index must be integer: ' . $index);
		}
	}

	final public static function throwTypeException(): void {
		if (static::IS_ENUM) {
			$availableValues = array_column(static::ITEM_TYPE::cases(), 'value');

			throw new TypeError('Expected value: ' . implode(', ', $availableValues));
		}

		throw new TypeError('Only ' . static::ITEM_TYPE . ' types can be assigned');
	}

	/**
	 * Вернуть значения массива
	 *
	 * @return ITEM_TYPE[]
	 */
	final public function getValues(): array {
		return $this->items;
	}

	/**
	 * Вернуть строковые или числовые представления массива
	 *
	 * @return string[]
	 */
	final public function getStringValues(): array {
		$itemsPrepared = $this->items;

		if (static::IS_ENUM) {
			foreach ($itemsPrepared as &$_item) {
				$_item = $_item->value;
			}
		}

		if (is_a(static::ITEM_TYPE, AbstractString::class, true)) {
			foreach ($itemsPrepared as &$_item) {
				$_item = (string) $_item;
			}
		}

		return $itemsPrepared;
	}

	/**
	 * Склеить массив в строку через запятую, строки взять в одинарные кавычки
	 *
	 * Вложенные массиы будут пропущены
	 */
	final public function implodeQuotes(): string {
		if (!$this->items) return "''";

		$itemsPrepared = $this->items;
		foreach ($itemsPrepared as &$_item) {
			if ($_item instanceof BackedEnum) {
				$_item = $_item->value;
			}

			if (is_int($_item)) continue;
			if (is_float($_item)) continue;

			if (is_array($_item)) $_item = '';

			if (is_bool($_item)) $_item = $_item ? '1' : '0';

			$_item = "'$_item'";
		}

		return implode(',', $itemsPrepared);
	}

	#[Override]
	final public function offsetExists(mixed $offset): bool {
		return array_key_exists($offset, $this->items);
	}

	/**
	 * @return ITEM_TYPE
	 */
	#[Override]
	final public function offsetGet(mixed $offset): mixed {
		if (!$this->offsetExists($offset)) {
			throw new RangeException('Index not found: ' . $offset);
		}

		return $this->items[$offset];
	}

	#[Override]
	final public function offsetSet(mixed $offset, mixed $value): void {
		if (!is_null($offset)) {
			self::checkIndex($offset);
		}

		$value = $this->prepareValue($value);

		if (is_null($offset)) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	#[Override]
	final public function offsetUnset(mixed $offset): void {
		if (array_key_exists($offset, $this->items)) {
			unset($this->items[$offset]);
		}
	}

	#[Override]
	final public function rewind(): void {
		$this->position = 0;
	}

	/**
	 * @return ITEM_TYPE
	 */
	#[Override]
	final public function current(): mixed {
		return $this->items[$this->position];
	}

	#[Override]
	final public function key(): int {
		return $this->position;
	}

	#[Override]
	final public function next(): void {
		++$this->position;
	}

	#[Override]
	final public function valid(): bool {
		return isset($this->items[$this->position]);
	}

	#[Override]
	final public function count(): int {
		return count($this->items);
	}

	/**
	 * Проверить и подготовить значнеие для вставки в массив
	 *
	 * Переопределить эту функцию при создании нового базового типа массива
	 *
	 * @return ITEM_TYPE
	 */
	abstract protected function prepareValue(mixed $value): mixed;

}
