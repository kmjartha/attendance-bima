<?php

namespace App\Core;

class Response
{
    public static function json($data, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data);
    }

    public static function redirect(string $url): string
    {
        header('Location: ' . $url);
        return '';
    }

    public static function abort(int $code, string $msg = ''): string
    {
        http_response_code($code);
        return "<h1>{$code}</h1><p>" . htmlspecialchars($msg) . "</p>";
    }
}
