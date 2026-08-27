<?php

namespace App\Core;

/**
 * Base Controller
 *
 * Handles view rendering inside the shared layout, JSON responses
 * for AJAX/API-style endpoints, and redirect helpers.
 */
abstract class Controller
{
    /**
     * Render a view file wrapped in the main layout.
     *
     * @param string $view  Dot notation, e.g. "products.index"
     * @param array  $data  Extracted into the view's local scope
     * @param string $layout Layout file (without .php) in Views/layouts
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            die("View not found: {$view}");
        }

        // Auth user available to every view for nav/role checks
        $authUser = Auth::user();

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = __DIR__ . "/../Views/layouts/{$layout}.php";
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    /**
     * Render a bare view with no layout (used for print/invoice pages).
     */
    protected function viewOnly(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            die("View not found: {$view}");
        }
        require $viewPath;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? base_url('/');
        header('Location: ' . $referer);
        exit;
    }

    protected function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Very small CSRF guard: validates the token on every POST.
     */
    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            http_response_code(419);
            die('Invalid or expired form submission (CSRF check failed). Go back and retry.');
        }
    }

    /**
     * Current CSRF token for this session, generating one on first use.
     * Single implementation shared by every controller — previously this
     * was copy-pasted privately into each controller, which meant a new
     * controller could easily forget it and render a form with no token.
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /**
     * HTML-escape a value for safe output. Use this (or the global e()
     * helper below) around any dynamic value printed into a view.
     */
    protected function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('e')) {
    /**
     * Global shorthand so views can write <?= e($value) ?> without
     * needing a Controller instance in scope.
     */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
