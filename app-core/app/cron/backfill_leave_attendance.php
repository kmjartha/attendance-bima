<?php
/**
 * BACKFILL SEKALI JALAN — Sinkronkan cuti/sakit yang SUDAH disetujui
 * SEBELUM perbaikan ini ada, ke tabel attendances.
 * ---------------------------------------------------------------------
 * Sama seperti masalah alpha dulu: kolom "Izin"/"Sakit" di laporan sudah
 * ada dari awal tapi SELALU 0, karena approve cuti tidak pernah menulis
 * apapun ke tabel attendances. VerifikasiController sudah diperbaiki utk
 * pengajuan yang di-approve MULAI SEKARANG — tapi pengajuan yang sudah
 * lebih dulu di-approve (histori lama) perlu di-backfill manual SEKALI
 * pakai script ini.
 *
 * AMAN dijalankan berkali-kali (idempotent) — baris yang sudah benar
 * (hadir/telat/izin/sakit) tidak akan ditimpa, cuma baris kosong atau
 * yang berstatus 'alpha' yang diisi/diperbaiki.
 *
 * CARA JALANKAN (sekali saja, lewat SSH/Terminal Hostinger kalau ada):
 *   php app-core/app/cron/backfill_leave_attendance.php
 *
 * Kalau tidak ada akses SSH, jalankan dari Terminal lokal (MAMP/XAMPP)
 * yang tersambung ke database yang sama, atau minta akses SSH sementara
 * dari support Hostinger.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script ini hanya boleh dijalankan lewat CLI, bukan browser.\n");
}

require_once dirname(__DIR__, 2) . '/config/env.php';

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', dirname(BASE_PATH));
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

$config = require BASE_PATH . '/config/app.php';
$db     = require BASE_PATH . '/config/database.php';

spl_autoload_register(function ($class) {
    $map = [
        'App\\Core\\'        => APP_PATH . '/core/',
        'App\\Controllers\\' => APP_PATH . '/controllers/',
        'App\\Models\\'      => APP_PATH . '/models/',
        'App\\Middleware\\'  => APP_PATH . '/middleware/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) { require $file; return; }
        }
    }
});

foreach (['auth', 'url', 'format', 'upload', 'face', 'policy'] as $helper) {
    require APP_PATH . '/helpers/' . $helper . '.php';
}

use App\Core\Database;
use App\Models\Attendance;

date_default_timezone_set($config['timezone'] ?? 'Asia/Makassar');
Database::init($db);
$pdo = Database::pdo();

$rows = $pdo->query("SELECT * FROM leave_requests WHERE status = 'approved' ORDER BY id")->fetchAll();
echo "Ditemukan " . count($rows) . " pengajuan cuti/sakit yang sudah disetujui.\n";

$attModel = new Attendance();
$totalWritten = 0;

foreach ($rows as $leave) {
    $written = $attModel->syncFromApprovedLeave($leave);
    if ($written > 0) {
        echo "  - Leave #{$leave['id']} (user_id={$leave['user_id']}, {$leave['jenis']}, {$leave['tanggal_mulai']} s/d {$leave['tanggal_selesai']}): {$written} baris ditulis/diperbaiki.\n";
    }
    $totalWritten += $written;
}

echo "\nSelesai. Total baris attendances yang ditulis/diperbaiki: {$totalWritten}.\n";
