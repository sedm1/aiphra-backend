<?php

namespace API\Params;

use Selector;
use API\Method\AbstractMethod;

trait FiltersTrait {

	/**
	 * Список фильтров по полям объекта
	 *
	 * {name: string, operator: Selector\Types\Operator, values: array}
	 *
	 * Использует поля модели
	 *
	 * Поля обязательное, если $id не указан
	 *
	 * @see AbstractMethod::MODEL
	 * @see Selector\Types\Operator
	 */
	public array $filters = [];

	/**
	 * Id объекта, для фильтрации объектов по id
	 *
	 * Только для моделей с полем id
	 */
	public ?int $id;

}
