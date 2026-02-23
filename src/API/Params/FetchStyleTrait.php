<?php

namespace API\Params;

use Selector\Types\FetchStyle;

/**
 * Данный трейт расширяет API метод поддержкой выбора FetchStyle
 *
 * Его требуется указывать во всех методах, которые его поддерживают.
 * Это такие методы, которые используют Selector с execFetch() без дополнительной обработки
 *
 * Например:
 * ```
 * protected function exec(): mixed {
 *    return $this->genSelector()->execFetch();
 * }
 * ```
 */
trait FetchStyleTrait {

	/**
	 * Определяет формат результата: коллекция, объект, значение
	 *
	 * Примеры:
	 * - fetchAll - получить коллекцию объектов
	 * - fetch - получить один объект
	 * - fetchColumn - получить свойство объекта
	 *
	 * @see FetchStyle
	 * @see Selector::execFetch() - см. реализацию
	 */
	public ?FetchStyle $fetch_style;

}
