<?php

const RATE_LIMIT_PER_SECOND = 5;

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
            $windowSecond = (int)$parts[0];
            $count = (int)$parts[1];
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

if ($count <= RATE_LIMIT_PER_SECOND) {
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

function rl_client_ip(): string {
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

    // Trust forwarding headers only when request came from known reverse proxy.
    if ( $remoteAddr !== '') {
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
    }

    if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        return $remoteAddr;
    }

    return 'unknown';
}

function rl_env_csv(string $name): array {
    $value = getenv($name);
    if ($value === false) {
        return [];
    }

    $items = preg_split('/[\s,]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($items)) {
        return [];
    }

    return array_values(array_unique($items));
}

function rl_storage_dir(): string {
    return rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'aiphra-rate-limit';
}
