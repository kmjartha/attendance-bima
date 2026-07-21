<?php

namespace App\Middleware;

use App\Core\Csrf;

class CsrfMiddleware
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;

        $token = Csrf::fromRequest();
        if (!Csrf::check($token)) {
            http_response_code(419);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF token invalid. Refresh halaman.']);
                exit;
            }
            echo '<div style="padding:48px;text-align:center;font-family:sans-serif">'
                . '<h1>419 - Page Expired</h1>'
                . '<p>Token CSRF tidak valid. Silakan refresh halaman dan coba lagi.</p></div>';
            exit;
        }
    }
}
