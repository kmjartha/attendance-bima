<?php

if (!function_exists('user')) {
    function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return !empty($_SESSION['user']);
    }
}

if (!function_exists('user_role')) {
    function user_role(): ?string
    {
        return $_SESSION['user']['role_name'] ?? null;
    }
}

if (!function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        return in_array(user_role(), $roles, true);
    }
}

if (!function_exists('is_pegawai')) {
    function is_pegawai(): bool
    {
        return has_role('Guru', 'Staff', 'Security', 'Manajerial');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('remember_me_value')) {
    function remember_me_value(array $user): string
    {
        $token = hash_hmac('sha256',
            'siabsen|'.$user['id'].'|'.$user['password'].'|'.$user['updated_at'].'|'.(\App\Core\App::$config['url'] ?? ''),
            'absensi-remember'
        );
        $payload = [
            'id' => (int)$user['id'],
            'token' => $token,
            'expires_at' => time() + 60 * 60 * 24 * 14,
        ];
        return base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('remember_me_login')) {
    function remember_me_login(): ?array
    {
        $cookie = $_COOKIE['remember_me'] ?? '';
        if ($cookie === '') return null;

        $payload = json_decode(base64_decode($cookie, true) ?: '', true);
        if (!is_array($payload) || empty($payload['id']) || empty($payload['token']) || empty($payload['expires_at'])) {
            return null;
        }
        if ((int)$payload['expires_at'] < time()) {
            setcookie('remember_me', '', time() - 3600, '/');
            return null;
        }

        $userModel = new \App\Models\User();
        $user = $userModel->findWithRole((int)$payload['id']);
        if (!$user) return null;

        $expected = hash_hmac('sha256',
            'siabsen|'.$user['id'].'|'.$user['password'].'|'.$user['updated_at'].'|'.(\App\Core\App::$config['url'] ?? ''),
            'absensi-remember'
        );

        return hash_equals($expected, (string)$payload['token']) ? $user : null;
    }
}
