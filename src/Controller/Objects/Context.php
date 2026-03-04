<?php

namespace Controller\Objects;

use Controller;
use Exception;

readonly class Context {

    public Controller\Types\Version $version;
    public Controller\Types\Oper $oper;
    public string $serviceName;
    public string $methodName;

    /**
     * @param string $apiPath `/{{ version }}/{{ oper }}/{{ serviceName }}/{{ methodName }}/`
     *
     * legacy format: `/{{ version }}/{{ oper }}/{{ serviceName }}/{{ methodName }}/`
     */
    public function __construct(string $apiPath) {
        $versionIndex = 0;
        $operIndex = 1;
        $serviceIndex = 2;
        $methodIndex = 3;

        $apiPath = trim($apiPath, '/');

        $apiPathes = explode('/', $apiPath);

        if (count($apiPathes) < 3) {
            throw new Exception('Invalid request name, check format, /{{ oper }}/{{ serviceName }}/{{ methodName }}', ERROR_CODE_REQUEST_NAME);
        }

        $version = Controller\Types\Version::tryFrom($apiPathes[$versionIndex]);
        if (!$version) throw new Exception("Request error: 'version'", ERROR_CODE_OPERATOR);
        $this->version = $version;

        $oper = Controller\Types\Oper::tryFrom($apiPathes[$operIndex]);
        if (!$oper) {
            throw new Exception("Request error: 'oper'", ERROR_CODE_OPERATOR);
        }

        $this->oper = $oper;
        $this->serviceName = $apiPathes[$serviceIndex];
        $this->methodName = implode('/', array_slice($apiPathes, $methodIndex));

        if (!$this->serviceName) {
            throw new Exception("Request error: 'Service name'", ERROR_CODE_SERVICE);
        }
    }

}
