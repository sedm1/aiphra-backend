<?php

namespace API\Types;

/**
 * Тип массива: Datetime[]
 *
 * @extends AbstractStringArray<Datetime>
 * @method Datetime[] getValues()
 * @method Datetime current()
 * @see Datetime
 */
class DatetimeArray extends AbstractStringArray {

	protected const string ITEM_TYPE = Datetime::class;

}
