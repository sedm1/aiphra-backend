<?php

namespace API\Params;

use API\Method\AbstractMethod;

trait OrdersTrait {

	/**
	 * Список полей объекта, по которым необходимо выполнить сортировку
	 *
	 * Поля могут быть строками или объектом: {name: string, direction: 'ASC' | 'DESC', orderValues: array, operator: string, values: array}
	 *
	 * Использует поля модели
	 *
	 * @see AbstractMethod::MODEL
	 */
	public array $orders = [];

}
