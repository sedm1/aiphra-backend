<?php

namespace Controller;

use API\Method\AbstractMethod;
use Controller;
use Exception;
use Services\Users;

final class V1 extends AbstractController {

    private const array ALLOWED_GET_METHODS = [];
    private const array PUBLIC_METHOD_CLASSES = [
        'Services\\Users\\Methods\\Reg\\Add',
    ];

    protected Controller\Objects\Context $context;

    public function init(): mixed {
        $this->context = new Controller\Objects\Context(implode('/', $this->page->getParams()));

        $apiClass = $this->getApiMethodClass();
        if (!$apiClass) {
            throw new Exception('Invalid API method', ERROR_CODE_METHOD);
        }
        if (!is_subclass_of($apiClass, AbstractMethod::class)) {
            throw new Exception('Invalid API method class', ERROR_CODE_METHOD);
        }
        $httpMethod = $this->validateHttpMethod($apiClass);
        $this->setAuth($apiClass);

        $api = new $apiClass();
        $api->setRawData(self::getRequestData($httpMethod));
        $result = $api->call();

        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new Exception('Cannot encode API response', ERROR_CODE_INTRENAL_SERVER_ERROR);
        }

        echo $json;
        exit();
    }

    /**
     * @return class-string<AbstractMethod>|null
     */
    private function getApiMethodClass(): ?string {
        $serviceName = $this->context->serviceName;
        $methodName = $this->context->methodName;
        $oper = $this->context->oper->value;

        $class = 'Services';
        $class .= "/$serviceName";
        $class .= '/Methods';
        if ($methodName) {
            $class .= "/$methodName";
        }
        $class .= "/$oper";

        $class = explode('/', $class);
        $class = array_map('ucfirst', $class);
        $class = implode('\\', $class);

        if (!class_exists($class)) {
            return null;
        }

        return $class;
    }

    private function validateHttpMethod(string $methodClass): string {
        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!is_string($httpMethod)) {
            throw new Exception('Method not allowed', ERROR_CODE_METHOD_NOT_ALLOWED);
        }

        $httpMethod = strtoupper($httpMethod);
        if ($httpMethod !== 'GET' && $httpMethod !== 'POST') {
            throw new Exception('Method not allowed', ERROR_CODE_METHOD_NOT_ALLOWED);
        }

        if ($httpMethod === 'GET' && !self::isGetAllowed($methodClass)) {
            throw new Exception('GET is not allowed for this API method', ERROR_CODE_METHOD_NOT_ALLOWED);
        }

        return $httpMethod;
    }

    private static function isGetAllowed(string $methodClass): bool {
        return array_any(self::ALLOWED_GET_METHODS, fn($serviceClass) => str_starts_with($methodClass, $serviceClass . '\\Methods\\'));

    }

    private function setAuth(string $apiClass): void {
        user()->reset();

        if (in_array($apiClass, self::PUBLIC_METHOD_CLASSES, true)) return;

        $accessToken = $this->extractBearerToken();
        if (!$accessToken) throw new Exception('Authorization required', ERROR_CODE_AUTH);

        $authUser = Users\Mods\Tokens::resolveAccessToken($accessToken);
        if (is_array($authUser)) {
            user()->set($authUser['id'], $authUser['email']);

            return;
        }

        $refreshToken = $this->extractRefreshToken();
        if (!$refreshToken) throw new Exception('Invalid access token', ERROR_CODE_AUTH);

        $nextTokens = Users\Mods\Tokens::refreshTokens($refreshToken);
        user()->set($nextTokens['user_id'], $nextTokens['email']);

        if (!headers_sent()) {
            header('X-Access-Token: ' . $nextTokens['access_token']);
            header('X-Refresh-Token: ' . $nextTokens['refresh_token']);
        }
    }

    private function extractBearerToken(): string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!is_string($header)) return '';

        if (!preg_match('~^\s*Bearer\s+(.+?)\s*$~i', $header, $matches)) return '';

        return trim($matches[1]);
    }

    private function extractRefreshToken(): string {
        $header = $_SERVER['HTTP_X_REFRESH_TOKEN'] ?? '';
        if (is_string($header) && trim($header) !== '') {
            return trim($header);
        }

        $cookie = $_COOKIE['refresh_token'] ?? '';
        if (is_string($cookie) && trim($cookie) !== '') {
            return trim($cookie);
        }

        return '';
    }

    private static function getRequestData(string $httpMethod): array {
        $requestData = [];
        if (is_array($_GET)) $requestData = array_merge($requestData, $_GET);

        if ($httpMethod === 'GET') return $requestData;

        if (is_array($_POST)) $requestData = array_merge($requestData, $_POST);

        $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
        $contentType = preg_replace('~;.*~', '', $contentType);
        if (!is_string($contentType)) $contentType = '';
        $contentType = trim(strtolower($contentType));

        if (!$contentType) return $requestData;

        switch ($contentType) {
            case 'application/json':
                $rawData = file_get_contents('php://input');
                if (!is_string($rawData) || trim($rawData) === '') {
                    return $requestData;
                }

                $jsonData = json_decode($rawData, true);
                if (!is_array($jsonData)) {
                    throw new Exception(
                        "Invalid Content-Type: $contentType (post-data must be of type object or array)",
                        ERROR_CODE_REQUEST_DATA,
                    );
                }

                $requestData = array_merge($requestData, $jsonData);
                break;
            default:
                throw new Exception("Invalid Content-Type: $contentType", ERROR_CODE_REQUEST_DATA);
        }

        return $requestData;
    }
}
