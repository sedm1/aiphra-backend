<?php

namespace Controller;

use API\Method\AbstractMethod;
use Controller;
use Exception;
use Services\Users;
use stdClass;
use Utils\Cookies;

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

        $response = $this->getResponse($result);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    private static function getResponse(mixed $result): stdClass {
        $response = new stdClass();
        $response->result = $result;
        if (core()->info) $response->info = core()->info;

        return $response;
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

        if ($httpMethod === 'GET' && !in_array($methodClass, self::ALLOWED_GET_METHODS)) {
            throw new Exception('GET is not allowed for this API method', ERROR_CODE_METHOD_NOT_ALLOWED);
        }

        return $httpMethod;
    }

    private function setAuth(string $apiClass): void {
        user()->reset();

        if (in_array($apiClass, self::PUBLIC_METHOD_CLASSES, true)) return;

        $accessToken = Cookies::get('access_token');
        if ($accessToken) {
            $authUser = Users\Methods\Reg\Mods\Tokens::resolveAccessToken($accessToken);
            if (is_array($authUser)) {
                user()->set($authUser['id'], $authUser['email']);

                return;
            }
        }

        $refreshToken = Cookies::get('refresh_token');
        if (!$refreshToken) {
            if ($accessToken) {
                throw new Exception('Invalid access token', ERROR_CODE_AUTH);
            }

            throw new Exception('Authorization required', ERROR_CODE_AUTH);
        }

        $nextTokens = Users\Methods\Reg\Mods\Tokens::refreshTokens($refreshToken);
        user()->set($nextTokens['user_id'], $nextTokens['email']);

        Cookies::set('access_token', $nextTokens['access_token'], getenv('AUTH_ACCESS_TTL'));
        Cookies::set('refresh_token', $nextTokens['refresh_token'], getenv('AUTH_REFRESH_TTL'));

        if (!headers_sent()) {
            header('X-Access-Token: ' . $nextTokens['access_token']);
            header('X-Refresh-Token: ' . $nextTokens['refresh_token']);
        }
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
