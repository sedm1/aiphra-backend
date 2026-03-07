<?php

namespace Controller;

use RuntimeException;

final class Page {
    private string $uri;
    private string $path;
    private array $params = [];
    private ?string $controllerClass = null;

    public function __construct(?string $uri = null) {
        $this->uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    public function setParams(): void {
        $trimmed = trim($this->path, '/');
        if ($trimmed === '') {
            $this->params = [];
            return;
        }

        $raw = explode('/', $trimmed);
        $params = [];
        foreach ($raw as $segment) {
            $segment = rawurldecode($segment);
            if ($segment === '') {
                continue;
            }
            if (str_ends_with($segment, '.php')) {
                $segment = substr($segment, 0, -4);
            }
            $params[] = $segment;
        }

        $this->params = $params;
    }

    public function setController(): void {
        $count = count($this->params);
        if ($count === 0) {
            $this->controllerClass = 'Controller\\E404';
            return;
        }

        for ($i = $count; $i >= 1; $i--) {
            $prefix = array_slice($this->params, 0, $i);
            $classPath = $this->segmentsToClassPath($prefix);

            if (class_exists('Controller\\' . $classPath)) {
                $this->controllerClass = 'Controller\\' . $classPath;
                return;
            }
        }

        $this->controllerClass = 'Controller\\E404';
    }

    public function dispatch(): void {
        $controllerClass = $this->controllerClass ?? 'Controller\\E404';
        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Controller not found: {$controllerClass}");
        }

        $controller = new $controllerClass($this);
        if (!method_exists($controller, 'init')) {
            throw new RuntimeException("Controller has no init(): {$controllerClass}");
        }

        $controller->init();
    }

    public function getParams(): array {
        return $this->params;
    }

    private function segmentsToClassPath(array $segments): string {
        $parts = [];
        foreach ($segments as $segment) {
            $parts[] = $this->segmentToClass($segment);
        }

        return implode('\\', $parts);
    }

    private function segmentToClass(string $segment): string {
        $segment = preg_replace('/[^a-zA-Z0-9_-]/', '', $segment);
        $parts = preg_split('/[-_]+/', $segment, -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_map(static fn(string $part): string => ucfirst(strtolower($part)), $parts);

        return implode('', $parts);
    }
}
