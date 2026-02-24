<?php

if (!isset($page)) {
    $page = $GLOBALS['page'] ?? null;
}

if (!$page instanceof Controller\Page) {
    throw new RuntimeException('Page was not initialized by router');
}

try {
    $page->dispatch();
} catch (\Throwable $e) {
    $code = (int) $e->getCode();
    if ($code < 400 || $code >= 600) {
        $code = 500;
    }
    http_response_code($code);
    echo $e->getMessage();
}
