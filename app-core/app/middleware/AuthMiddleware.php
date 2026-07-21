<?php

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!empty($_SESSION['user'])) {
            return;
        }

        $user = remember_me_login();
        if ($user) {
            $_SESSION['user'] = [
                'id'           => (int)$user['id'],
                'niy'          => $user['niy'],
                'nama'         => $user['nama'],
                'jabatan'      => $user['jabatan'],
                'role_id'      => (int)$user['role_id'],
                'role_name'    => $user['role_name'],
                'foto_profile' => $user['foto_profile'],
            ];
            return;
        }

        header('Location: ' . url('/login'));
        exit;
    }
}
