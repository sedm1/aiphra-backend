<?php

namespace API\Params;

trait OffsetTrait {

	/**
	 * Число объектов, которое необходимо пропустить при получении результата
	 *
	 * Используется в паре с limit
	 */
	public int $offset = 0;

}
