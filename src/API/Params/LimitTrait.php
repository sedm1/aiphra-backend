<?php

namespace API\Params;

trait LimitTrait {

	/**
	 * Количество объектов, которые необходимо получить в результате
	 *
	 * Используется в паре с offset
	 */
	public ?int $limit;

}
