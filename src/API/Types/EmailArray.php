<?php

namespace API\Types;

/**
 * Тип массива: Email[]
 *
 * @extends AbstractStringArray<Email>
 * @method Email[] getValues()
 * @method Email current()
 * @see Email
 */
class EmailArray extends AbstractStringArray {

	protected const string ITEM_TYPE = Email::class;

}
