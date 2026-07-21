<?php

namespace App\Middleware;

/**
 * Per-route role check. Karena middleware kita statis,
 * kita pakai pendekatan: kelas turunan untuk tiap role-set.
 * Untuk Stage 1 cukup AuthMiddleware. Pengecekan role spesifik
 * dilakukan langsung di controller via has_role().
 */
class RoleMiddleware
{
    protected array $allowed = [];

    public function handle(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . url('/login'));
            exit;
        }
        $role = $_SESSION['user']['role_name'] ?? '';
        if (!empty($this->allowed) && !in_array($role, $this->allowed, true)) {
            http_response_code(403);
            echo '<div style="padding:48px;text-align:center;font-family:sans-serif">'
                . '<h1>403 - Akses Ditolak</h1>'
                . '<p>Anda tidak memiliki akses ke halaman ini.</p>'
                . '<a href="' . url('/dashboard') . '">Kembali ke Dashboard</a></div>';
            exit;
        }
    }
}

class HrdOnly extends RoleMiddleware
{
    protected array $allowed = ['HRD'];
}

class HrdKepsek extends RoleMiddleware
{
    protected array $allowed = ['HRD', 'Kepsek'];
}
