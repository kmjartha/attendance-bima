<?php

namespace App\Core;

class View
{
    public function render(string $view, array $data = [], string $layout = 'app'): string
    {
        $viewFile   = APP_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
        $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            return "<pre>View tidak ditemukan: {$view}</pre>";
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if (!is_file($layoutFile)) {
            return $content;
        }

        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }
}
