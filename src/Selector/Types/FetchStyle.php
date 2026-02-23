<?php

namespace Selector\Types;

use PDO;

/**
 * Формат результата в основном используется для Get запросов, поддерживается не всеми запросами в полной мере
 *
 * Указанием формата результата позволяет получать данные одного и того же метода в разном формате
 *
 * Данный параметр влияет на тип возвращаемого результата
 *
 * Данные параметр помогает избежать ненужных преобразований результатов на клиенте
 *
 * Напоминает логику работу PDO Fetch: https://www.php.net/manual/ru/pdostatement.fetch.php
 */
enum FetchStyle: string {

	/**
	 * Список объектов
	 *
	 * Является значением по умолчанию
	 */
	case FetchAll = 'fetchAll';

	/**
	 * Список объектов в виде массивов, где элементы массива - это свойства объекта, перечисленные с сохранением своего порядка
	 *
	 * Полезно для экономии трафика и ускорения передачи данных в формате json
	 */
	case FetchAllNum = 'fetchAllNum';

	/**
	 * Список значений одного свойства по каждому объекту
	 */
	case FetchAllColumn = 'fetchAllColumn';

	/**
	 * Один объект
	 */
	case Fetch = 'fetch';

	/**
	 * Одно свойство одного объека
	 */
	case FetchColumn = 'fetchColumn';

	/**
	 * Объекты с группировкой
	 */
	case FetchGroup = 'fetchGroup';

	/**
	 * Значения свойства объектов с группировкой
	 */
	case FetchGroupColumn = 'fetchGroupColumn';

	/**
	 * Объекты с группировкой с объединением дублей
	 */
	case FetchUnique = 'fetchUnique';

	/**
	 * Значения свойства объектов с группировкой с объединением дублей
	 */
	case FetchUniqueNum = 'fetchUniqueNum';

	/**
	 * Список значений одного свойства по каждому объекту с объединением дублей
	 */
	case FetchUniqueColumn = 'fetchUniqueColumn';

	/**
	 * Внутренний объект метода Selector
	 */
	case Selector = 'selector';

	public function getDbhFetchStyle(): int {
		return match ($this) {
			self::FetchAllNum => PDO::FETCH_NUM,
			self::FetchAllColumn, self::FetchColumn => PDO::FETCH_COLUMN,
			self::FetchGroup => PDO::FETCH_GROUP | PDO::FETCH_ASSOC,
			self::FetchGroupColumn => PDO::FETCH_GROUP | PDO::FETCH_COLUMN,
			self::FetchUnique => PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC,
			self::FetchUniqueNum => PDO::FETCH_UNIQUE | PDO::FETCH_NUM,
			self::FetchUniqueColumn => PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN,
			default => PDO::FETCH_ASSOC,
		};
	}

}
