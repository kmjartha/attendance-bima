<?php

return [
    'name' => 'SiAbsen',
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