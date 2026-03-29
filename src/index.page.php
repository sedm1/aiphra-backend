<?php

if (!isset($page)) {
    $page = $GLOBALS['page'] ?? null;
}

if (!$page instanceof Controller\Page) {
    throw new RuntimeException('Page was not initialized by router');
}

try {
    $page->dispatch();
} catch (Throwable $e) {
    http_response_code(200);

    $response = new stdClass();
    $response->errors = [
        [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ];
    if (core()->info) $response->info = core()->info;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
