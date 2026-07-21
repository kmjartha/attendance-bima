<?php

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    die('.env file not found');
}

$env = parse_ini_file($envPath);

if ($env === false) {
    die('.env parsing failed');
}

foreach ($env as $key => $value) {
    defined($key) || define($key, $value);
}