<?php

namespace API\Types;

/**
 * Тип массива: UrlShortUTF[]
 *
 * @extends AbstractStringArray<UrlShortUTF>
 * @method UrlShortUTF[] getValues()
 * @method UrlShortUTF current()
 * @see UrlShortUTF
 */
final class UrlShortUTFArray extends UrlArray {

	protected const string ITEM_TYPE = UrlShortUTF::class;

}
