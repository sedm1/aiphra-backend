<?php

namespace Utils;

class Url {

	public static function validHost(string $host): bool {
		if (self::validIP($host)) return true;
		if (self::validDomain($host)) return true;

		return false;
	}

	public static function validDomain(string $domain): bool {
		// нормализуем домен из punycode в Unicode перед проверкой
		$domain = self::fromPuny($domain);

		$regexp = self::getDomainRegexp();

		return preg_match("/^$regexp$/ui", $domain);
	}

	/**
	 * Раскодировать все не ASCII символы в домене URL
	 *
	 * Пример: `xn--d1acufc.xn--p1ai` => `пример.рф`
	 */
	public static function fromPuny(string $url): string {
		$url = trim($url);
		if ($url === '') return $url;

		// Если это просто домен без пути
		if (!str_contains($url, 'xn--')) return $url;
		if (!str_contains($url, '/')) return (string) idn_to_utf8($url, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

		$host = self::extractHost($url);
		$hostNormal = strtolower($host);
		$hostNormal = (string) idn_to_utf8($hostNormal, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		if ($host !== $hostNormal) $url = str_replace($host, $hostNormal, $url);

		return $url;
	}

	/**
	 * Закодировать все не ASCII символы в домене URL в punycode
	 *
	 * Пример: `пример.рф` => `xn--d1acufc.xn--p1ai`
	 */
	public static function toPuny(string $url): string {
		$url = trim($url);
		if ($url === '') return $url;

		// Если это просто домен без пути
		if (!str_contains($url, '/')) return (string) idn_to_ascii($url);

		$host = self::extractHost($url);
		$hostNormal = strtolower($host);
		$hostNormal = (string) idn_to_ascii($hostNormal);
		if ($host !== $hostNormal) $url = str_replace($host, $hostNormal, $url);

		return $url;
	}

	/**
	 * Получить имя хоста из URL
	 */
	public static function extractHost(string $url): string {
		return (string) preg_replace('~(?:^\w+://)?([^:/]+).*~i', '$1', $url);
	}

	/**
	 * Привести url к стандартному виду (не в пуникоде и хост в нижнем регистре)
	 */
	public static function normalize(string $url): string {
		$url = self::fromPuny($url);
		$url = self::hostToLowerCase($url);

		return $url;
	}

	/**
	 * Привести хост в URL к нижнему регистру
	 */
	public static function hostToLowerCase(string $url): string {
		$host = self::extractHost($url);
		$hostNormal = strtolower($host);
		if ($host !== $hostNormal) $url = str_replace($host, $hostNormal, $url);

		return $url;
	}

	public static function validIP(string $ip): bool {
		return (bool) filter_var($ip, FILTER_VALIDATE_IP);
	}

	public static function getDomainRegexp(): string {
		$regexpASCII = '(?:ru|com|abbott|ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|boo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|coop|cr|cs|gb|cu|cv|cx|cy|cyou|cz|dd|de|dj|dk|dm|do|dz|ec|eco|edu|ee|eg|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|icu|id|ie|il|im|in|int|info|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|live|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mh|mil|mg|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|ng|ni|nl|no|np|nr|nu|nz|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sx|sy|sz|tc|td|tech|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vn|vu|wf|ws|ye|yt|za|zm|zw|academy|agency|art|arte|audible|audio|band|bargains|bike|blog|boutique|builders|buzz|bargains|bike|boutique|builders|cab|camera|camp|cam|careers|center|cheap|clothing|club|codes|coffee|company|computer|condos|construction|contractors|cool|cruises|dance|dating|democrat|diamonds|directory|domains|education|email|enterprises|equipment|estate|events|expert|exposed|farm|flights|florist|foundation|futbol|gallery|gal|gift|glass|graphics|guitars|guru|holdings|holiday|house|immobilien|kitchen|land|lighting|limo|link|maison|management|marketing|men|menu|moe|ninja|partners|photo|photography|photos|pics|plumbing|productions|properties|recipes|rent|rentals|repair|review|reviews|sandvik|sexy|shoes|singles|social|solar|solutions|support|systems|tattoo|technology|tienda|tips|today|training|travel|vacations|ventures|viajes|villas|voyage|watch|works|zone|xxx|ads|associates|booking|business|ceo|ecom|forum|gives|global|gmbh|inc|institute|insure|lifeinsurance|llc|llp|ltd|ltda|mba|new|news|ngo|press|sarl|services|srl|studio|trade|trading|wiki|xin|auction|bid|blackfriday|buy|capital|charity|claims|compare|coupon|coupons|deal|dealer|deals|delivery|discount|exchange|flowers|free|furniture|gifts|gripe|grocery|jewelry|kaufen|lotto|parts|plus|promo|qpon|racing|rsvp|sale|salon|save|seat|shop|show|shopping|silk|spa|store|supplies|supply|taxi|tickets|tires|tools|toys|watches|college|courses|degree|ged|phd|prof|scholarships|school|schule|science|shiksha|study|translations|university|beer|cafe|catering|cityeats|cooking|diet|food|organic|pet|pizza|pub|rest|restaurant|soy|wine|blue|circle|dot|duck|fast|final|finish|fire|fun|fyi|goo|got|green|here|horse|how|ieee|jot|joy|like|limited|makeup|meme|mint|moi|moto|monster|now|nowruz|ong|onl|ooo|page|pars|pid|pink|play|plus|read|red|reise|reisen|rocks|safe|safety|seek|select|sky|smile|spot|sucks|talk|top|trust|uno|vin|vodka|web|wed|win|winners|wow|wtf|xyz|yamaxun|you|zero|abudhabi|africa|alsace|amsterdam|aquitaine|arab|barcelona|bayern|berlin|boston|broadway|brussels|budapest|bzh|capetown|cologne|corsica|country|cymru|desi|doha|dubai|durban|earth|eus|gent|hamburg|helsinki|international|irish|istanbul|joburg|kiwi|koeln|kyoto|london|madrid|market|melbourne|miami|nagoya|nrw|nyc|okinawa|osaka|paris|persiangulf|place|quebec|rio|roma|ryukyu|saarland|scot|shia|stockholm|stream|swiss|sydney|taipei|tatar|thai|tirol|tokyo|vegas|vlaanderen|wales|wanggou|wien|world|yokohama|zuerich|clinic|dental|dentist|docs|doctor|health|healthcare|hiv|hospital|med|medical|pharmacy|physio|rehab|surgery|auto|autos|bio|boats|cars|cleaning|consulting|design|energy|industries|motorcycles|adult|baby|beauty|beknown|best|bet|bingo|bom|cards|community|contact|dad|diy|dog|express|family|fan|fans|fashion|garden|gay|giving|group|guide|hair|halal|hiphop|imamat|jetzt|kid|kids|kim|kinder|latino|lgbt|lifestyle|style|living|love|luxe|luxury|moda|mom|navy|pets|poker|porn|republican|vip|vision|vote|voting|voto|wedding|feedback|film|media|mov|movie|movistar|music|pictures|radio|show|song|theater|theatre|tunes|video|accountant|accountants|analytics|bank|banque|broker|cash|cashbackbonus|cfd|cpa|credit|creditcard|finance|financial|financialaid|fund|gold|gratis|investments|ira|loan|loans|markets|money|mortgage|mutual|mutualfunds|pay|reit|prime|security|yun|abogado|airforce|archi|architect|army|attorney|author|dds|engineer|engineering|esq|law|lawyer|legal|retirement|vet|apartments|casa|case|forsale|haus|homes|lease|property|realestate|realtor|realty|room|baseball|basketball|coach|cricket|fish|fishing|fit|fitness|football|game|games|golf|hockey|juegos|mls|rodeo|rugby|run|ski|soccer|sport|sports|spreadbetting|surf|team|tennis|yoga|app|box|chat|click|cloud|comsec|bot|data|date|dev|digital|download|drive|call|fail|help|host|hosting|lol|map|mobile|network|online|original-tor|phone|report|search|secure|site|software|space|storage|tube|webcam|webs|website|weibo|zip|active|casino|christmas|hangout|hoteis|hotel|hoteles|hotels|meet|party|tour|tours|bible|church|catholic|faith|indians|islam|ismaili|memorial|moscow|actor|bar|black|build|care|city|direct|immo|ink|life|tax|town|work|one|insure|saxo|sex|shell|weber|world|yandex|ovh|rip|car|ist|lat|lol|bond|om|skin|day|quest|wang|sbs|dhl|krd|ing|yachts|gdn|foo|frl|group|canon|locker|nexus|komatsu|google)';
		$regexpUnicode = '(?:рф|дети|онлайн|сайт|укр|католик|ком|москва|орг|рус|бел|бг|ישראל|қаз)';
		$_w = '(?:[^\b\s.\/`!-,:-@\[-^{-¿]|&)';

		return "(?:$_w{1})(?:(?:\.?$_w))*\.(?:$regexpASCII|$regexpUnicode)\/?";
	}

}
