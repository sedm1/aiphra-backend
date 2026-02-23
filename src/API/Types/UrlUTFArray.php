<?php

namespace API\Types;

/**
 * Тип массива: UrlUTF[]
 *
 * @extends AbstractStringArray<UrlUTF>
 * @method UrlUTF[] getValues()
 * @method UrlUTF current()
 */
final class UrlUTFArray extends UrlArray {

	protected const string ITEM_TYPE = UrlUTF::class;

}
