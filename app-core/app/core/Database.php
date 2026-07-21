<?php

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function init(array $cfg): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['driver'], $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']
        );
        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
            $timezone = date_default_timezone_get();
            $offset = (new \DateTime('now', new \DateTimeZone($timezone)))->format('P');
            self::$pdo->exec("SET time_zone = '{$offset}'");
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                "Koneksi database gagal. Pastikan service MySQL aktif & database '{$cfg['database']}' sudah di-import. ({$e->getMessage()})"
            );
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database belum diinisialisasi.');
        }
        return self::$pdo;
    }
}
