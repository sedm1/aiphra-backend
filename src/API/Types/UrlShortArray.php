<?php

namespace API\Types;

/**
 * Тип массива: UrlShort[]
 *
 * @extends AbstractStringArray<UrlShort>
 * @method UrlShort[] getValues()
 * @method UrlShort current()
 * @see UrlShort
 */
final class UrlShortArray extends UrlArray {

	protected const string ITEM_TYPE = UrlShort::class;

}
