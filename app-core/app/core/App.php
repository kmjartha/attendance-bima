<?php

namespace App\Core;

class App
{
    public static array $config = [];

    public static function run(Router $router, array $config): void
    {
        self::$config = $config;
        date_default_timezone_set($config['timezone'] ?? 'UTC');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Strip app subfolder prefix (e.g. /absensi-sekolah or /absensi-sekolah/public)
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir  = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $baseDir    = $scriptDir;
        if (str_ends_with($scriptDir, '/public')) {
            $baseDir = substr($scriptDir, 0, -strlen('/public'));
        }
        if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }
        $uri = '/' . ltrim($uri, '/');

        try {
            $router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            http_response_code(500);
            if ((self::$config['env'] ?? 'local') === 'local') {
                echo '<pre style="padding:24px;font-family:monospace;background:#fff5f5;color:#7a1212;border-left:6px solid #c53030">';
                echo "ERROR: " . htmlspecialchars($e->getMessage()) . "\n\n";
                echo htmlspecialchars($e->getTraceAsString());
                echo '</pre>';
            } else {
                echo '<h1>500 - Internal Server Error</h1>';
            }
        }
    }
}
