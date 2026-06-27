<?php

namespace App\Core;

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function render(string $view, array $data = [], string $layout = 'app'): string
    {
        return $this->view->render($view, $data, $layout);
    }

    protected function redirect(string $path): string
    {
        header('Location: ' . url($path));
        return '';
    }

    protected function back(): string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $ref);
        return '';
    }

    protected function json($data, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $msg): void
    {
        $_SESSION['_flash'][$type] = $msg;
    }
}
