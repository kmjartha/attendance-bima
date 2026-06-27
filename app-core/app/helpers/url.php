<?php

use App\Core\App;

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(App::$config['url'] ?? '', '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $scriptDir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

            $appDir = $scriptDir;
            if (str_ends_with($scriptDir, '/public')) {
                $appDir = substr($scriptDir, 0, -strlen('/public')) ?: '/';
            }

            if ($scriptDir !== $appDir && (str_starts_with($requestPath, $scriptDir . '/') || $requestPath === $scriptDir)) {
                $base = $scheme . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
            } elseif ($requestPath === '/' || str_starts_with($requestPath, $appDir . '/') || $requestPath === $appDir) {
                $base = $scheme . '://' . $host . ($appDir === '/' ? '' : $appDir);
            } else {
                $base = $scheme . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
            }
            $base = rtrim($base, '/');
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path = null): string
    {
        if (!$path) return asset('images/default-avatar.png');
        return url('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('current_url')) {
    function current_url(): string
    {
        return ($_SERVER['REQUEST_URI'] ?? '/');
    }
}

if (!function_exists('is_active')) {
    function is_active(string $pattern, string $class = 'active'): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // Remove script directory prefix so routes work when app is in subfolder
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
            if ($uri === '') $uri = '/';
        }
        return str_starts_with($uri, $pattern) ? $class : '';
    }
}

if (!function_exists('is_active_exact')) {
    function is_active_exact(string $pattern, string $class = 'active'): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // Remove script directory prefix (same logic as is_active)
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
            if ($uri === '') $uri = '/';
        }
        // Normalize by trimming trailing slashes so "/absensi" and "/absensi/" match
        $u = rtrim($uri, '/');
        $p = rtrim($pattern, '/');
        if ($u === '') $u = '/';
        if ($p === '') $p = '/';
        return $u === $p ? $class : '';
    }
}
