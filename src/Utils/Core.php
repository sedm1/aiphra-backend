<?php

namespace Utils;

use Utils;

final class Core {

    use Utils\Core\EmailTrait;
    use Utils\Core\InfoTrait;

    public function getSiteHost(): string {
        return getenv('SITE_HOST');
    }

    public function getApiSiteHost(): string {
        return getenv('API_SITE_HOST');
    }
}
