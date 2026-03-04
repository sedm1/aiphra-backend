<?php

namespace API\Types;

/**
 * Тип массива: Date[]
 *
 * @extends AbstractStringArray<Date>
 * @method Date[] getValues()
 * @method Date current()
 * @see Date
 */
class DateArray extends AbstractStringArray {

	protected const string ITEM_TYPE = Date::class;

}
