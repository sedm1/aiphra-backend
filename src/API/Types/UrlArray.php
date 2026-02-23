<?php

namespace API\Types;

/**
 * Тип массива: Url[]
 *
 * @extends AbstractStringArray<Url>
 * @method Url[] getValues()
 * @method Url current()
 * @see Url
 */
class UrlArray extends AbstractStringArray {

	protected const string ITEM_TYPE = Url::class;

}
