<?php
declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'layouts/main'): void
    {
        (new View($template, $data))->withLayout($layout)->render();
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    protected function redirect(string $path): void
    {
        $base = rtrim(CFG['app']['url'], '/');
        header('Location: ' . $base . $path);
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login?reason=session');
        }
        // מוודא שה-CSRF token קיים בsession לכל דף מוגן
        $this->csrfToken();

        // כפיית שינוי סיסמא זמנית — מותר רק ל-/set-password ול-/logout
        if (!empty($_SESSION['must_change_password'])) {
            $uri = strtok($_SERVER['REQUEST_URI'], '?');
            $base = parse_url(CFG['app']['url'], PHP_URL_PATH) ?? '';
            if ($base) $uri = substr($uri, strlen($base));
            $uri = '/' . trim($uri, '/');
            $allowed = ['/set-password', '/logout', '/login'];
            if (!in_array($uri, $allowed, true)) {
                $this->redirect('/set-password?reason=must_change');
            }
        }
    }

    protected function requirePermission(string $key): void
    {
        $this->requireAuth();
        if (!Auth::can($key)) {
            // POST/AJAX → JSON; GET → HTML 403 page
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                $this->json(['error' => 'אין הרשאה לפעולה זו'], 403);
            }
            http_response_code(403);
            $this->view('pages/403', [], 'layouts/main');
            exit;
        }
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            $this->json(['error' => 'CSRF token invalid'], 419);
        }
    }
}
