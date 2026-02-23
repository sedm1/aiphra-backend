<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'ok',
    'ts' => gmdate('c'),
], JSON_UNESCAPED_SLASHES);
