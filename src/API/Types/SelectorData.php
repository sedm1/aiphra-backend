<?php

namespace API\Types;

use Selector\Types\FetchStyle;

/**
 * Объект параметров Selector
 */
final readonly class SelectorData extends AbstractObject {

	public function __construct(
		public array $fields = [],
		public array $filters = [],
		public ?int $id = null,
		public array $orders = [],
		public ?int $limit = null,
		public int $offset = 0,
		public ?FetchStyle $fetch_style = null,
	) {}

}
