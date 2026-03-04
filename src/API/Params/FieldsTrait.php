<?php

namespace API\Params;

use API\Method\AbstractMethod;

trait FieldsTrait {

	/**
	 * Список полей объекта, которые надо вернуть в результате
	 *
	 * Если запрос поддерживает параметр `fetch_style`, формат ответа может быть разным, `fields` будет влиять на содержание данных в этом ответе
	 *
	 * Использует поля модели
	 *
	 * @see AbstractMethod::MODEL
	 */
	public array $fields = [];

}
