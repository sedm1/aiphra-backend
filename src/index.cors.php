<?php

function cors_parse_origins(string $raw): array {
    $items = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($items)) {
        return [];
    }

    return array_values(array_unique($items));
}

function cors_origin_is_loopback(string $origin): bool {
    $parts = parse_url($origin);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));
    if (($scheme !== 'http' && $scheme !== 'https') || $host === '') {
        return false;
    }

    return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
}

function cors_allowed_origin(?string $origin): ?string {
    if (!is_string($origin) || $origin === '') {
        return null;
    }

    if (cors_origin_is_loopback($origin)) {
        return $origin;
    }

    $configured = cors_parse_origins((string)getenv('CORS_ALLOWED_ORIGINS'));
    if (in_array($origin, $configured, true)) {
        return $origin;
    }

    return null;
}

function apply_cors(): void {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $allowedOrigin = cors_allowed_origin($origin);

    if ($origin !== '') {
        header('Vary: Origin');
    }

    if ($allowedOrigin !== null) {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Expose-Headers: Retry-After, X-Access-Token, X-Refresh-Token');
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'OPTIONS') {
        return;
    }

    if ($allowedOrigin === null && $origin !== '') {
        http_response_code(ERROR_CODE_FORBIDDEN);
        echo json_encode([
            'error' => true,
            'message' => 'CORS origin is not allowed',
        ]);
        exit;
    }

    $requestHeaders = trim((string)($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? ''));
    if ($requestHeaders === '') {
        $requestHeaders = 'Content-Type, X-Requested-With, Authorization, X-Refresh-Token';
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: ' . $requestHeaders);
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}

// Apply CORS policy for every request (including preflight OPTIONS).
apply_cors();
