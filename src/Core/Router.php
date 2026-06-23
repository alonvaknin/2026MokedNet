<?php
declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string|callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string|callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = strtok($_SERVER['REQUEST_URI'], '?');

        // Strip base path (e.g. /v2/public)
        $base = parse_url(CFG['app']['url'], PHP_URL_PATH) ?? '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        // נרמול: הסר /index.php מהסוף אם קיים (כשנכנסים ישירות לקובץ)
        if (str_ends_with($uri, '/index.php')) {
            $uri = substr($uri, 0, -10);
        }

        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        // Exact match
        $handler = $this->routes[$method][$uri] ?? null;

        // Param match e.g. /stores/{id}
        $params = [];
        if (!$handler) {
            foreach ($this->routes[$method] ?? [] as $pattern => $h) {
                $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';
                if (preg_match($regex, $uri, $m)) {
                    $handler = $h;
                    $params  = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                    break;
                }
            }
        }

        if (!$handler) {
            http_response_code(404);
            echo '404 — עמוד לא נמצא (' . htmlspecialchars($uri) . ')';
            return;
        }

        if (is_callable($handler)) {
            $handler(...array_values($params));
            return;
        }

        [$class, $action] = explode('@', $handler);
        $controller = new $class();
        $controller->$action(...array_values($params));
    }
}
