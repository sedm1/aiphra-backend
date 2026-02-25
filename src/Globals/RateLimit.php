<?php

function rl_env_bool(string $name, bool $default): bool {
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }

    $normalized = strtolower(trim($value));
    if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
        return false;
    }

    return $default;
}

function rl_env_int(string $name, int $default): int {
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }

    if (!is_numeric($value)) {
        return $default;
    }

    return (int) $value;
}

function rl_client_ip(): string {
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwardedFor !== '') {
        $parts = explode(',', $forwardedFor);
        foreach ($parts as $part) {
            $candidate = trim($part);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    $xRealIp = $_SERVER['HTTP_X_REAL_IP'] ?? '';
    if ($xRealIp !== '' && filter_var($xRealIp, FILTER_VALIDATE_IP)) {
        return $xRealIp;
    }

    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        return $remoteAddr;
    }

    return 'unknown';
}

function rl_storage_dir(): string {
    return rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'aiphra-rate-limit';
}

function apply_global_rate_limit(): void {
    $enabled = rl_env_bool('RATE_LIMIT_ENABLED', true);
    if (!$enabled) {
        return;
    }

    $requestsPerSecond = rl_env_int('RATE_LIMIT_REQUESTS_PER_SECOND', 2);
    if ($requestsPerSecond < 1) {
        return;
    }

    $storageDir = rl_storage_dir();
    if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
        return;
    }

    $ip = rl_client_ip();
    $key = sha1($ip);
    $filePath = $storageDir . DIRECTORY_SEPARATOR . $key . '.txt';

    $handle = fopen($filePath, 'c+');
    if ($handle === false) {
        return;
    }

    $currentSecond = time();
    $windowSecond = $currentSecond;
    $count = 0;

    if (flock($handle, LOCK_EX)) {
        rewind($handle);
        $raw = stream_get_contents($handle);

        if (is_string($raw) && $raw !== '') {
            $parts = explode(':', trim($raw), 2);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $windowSecond = (int) $parts[0];
                $count = (int) $parts[1];
            }
        }

        if ($windowSecond !== $currentSecond) {
            $windowSecond = $currentSecond;
            $count = 0;
        }

        $count++;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $windowSecond . ':' . $count);
        fflush($handle);

        flock($handle, LOCK_UN);
    }

    fclose($handle);

    $retryAfter = 1;
    $remaining = max(0, $requestsPerSecond - $count);
    $resetAt = $currentSecond + 1;

    if (!headers_sent()) {
        header('X-RateLimit-Limit: ' . $requestsPerSecond);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $resetAt);
    }

    if ($count <= $requestsPerSecond) {
        return;
    }

    http_response_code(ERROR_CODE_SERVER_TOO_MANY_REQUESTS);
    if (!headers_sent()) {
        header('Retry-After: ' . $retryAfter);
    }

    echo json_encode([
        'error' => true,
        'message' => 'Too many requests'
    ]);
    exit;
}
