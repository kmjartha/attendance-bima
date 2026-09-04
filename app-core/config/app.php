<?php

return [
    'name' => 'SiAbsen',
    // Sebelumnya key 'env' tidak pernah di-set di sini, jadi App::run()
    // selalu fallback ke 'local' (lihat App::$config['env'] ?? 'local')
    // walaupun APP_ENV di .env sudah diisi 'production'. Akibatnya pesan
    // error mentah (SQL, path server, stack trace) selalu ditampilkan
    // langsung ke user di semua environment, termasuk production.
    'env'  => defined('APP_ENV') ? APP_ENV : 'local',
    'url'  => APP_URL,
    'master_password' => defined('MASTER_PASSWORD') ? MASTER_PASSWORD : '',
    'timezone' => 'Asia/Makassar',
    'upload' => [
        'profile_max' => 2 * 1024 * 1024, // 2 MB
    ],
    'face' => [
        'distance_threshold' => 0.60, // skor minimal 40% agar absensi diterima
    ],
];