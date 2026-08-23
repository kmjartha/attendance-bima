<?php
/**
 * BACKFILL SEKALI JALAN — Isi tabel leave_request_dates dari data
 * leave_requests yang sudah ada, SEBELUM perbaikan "1 pengajuan bisa
 * mencakup beberapa tanggal (termasuk yang tidak berurutan)" ini ada.
 * ---------------------------------------------------------------------
 * Setiap baris leave_requests lama diperluas per-hari dari tanggal_mulai
 * s/d tanggal_selesai menjadi baris-baris leave_request_dates (keterangan
 * per-tanggal dikosongkan krn data lama tidak punya info sedetail itu —
 * catatan umum tetap ada di leave_requests.alasan).
 *
 * AMAN dijalankan kapan saja, berkali-kali, bahkan SETELAH ada pengajuan
 * baru lewat sistem yang baru (yang tanggalnya sudah tersebar/tidak
 * berurutan) — script ini HANYA menyentuh baris leave_requests yang
 * BELUM punya rincian tanggal sama sekali di leave_request_dates.
 *
 * CARA JALANKAN (sekali saja, setelah migration_leave_request_dates.sql
 * dijalankan lebih dulu):
 *   php app-core/app/cron/backfill_leave_request_dates.php
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
        'App\\Core\\'   => APP_PATH . '/core/',
        'App\\Models\\' => APP_PATH . '/models/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) { require $file; return; }
        }
    }
});

use App\Core\Database;

date_default_timezone_set($config['timezone'] ?? 'Asia/Makassar');
Database::init($db);
$pdo = Database::pdo();

$rows = $pdo->query(
    "SELECT lr.id, lr.tanggal_mulai, lr.tanggal_selesai
     FROM leave_requests lr
     WHERE NOT EXISTS (SELECT 1 FROM leave_request_dates lrd WHERE lrd.leave_request_id = lr.id)
     ORDER BY lr.id"
)->fetchAll();
echo "Ditemukan " . count($rows) . " pengajuan cuti/sakit yang BELUM punya rincian tanggal.\n";

$insert = $pdo->prepare(
    "INSERT IGNORE INTO leave_request_dates (leave_request_id, tanggal, keterangan) VALUES (?, ?, NULL)"
);

$totalInserted = 0;
foreach ($rows as $row) {
    $cursor = strtotime($row['tanggal_mulai']);
    $end    = strtotime($row['tanggal_selesai']);
    if ($cursor === false || $end === false || $cursor > $end) {
        echo "  - Leave #{$row['id']}: tanggal tidak valid, dilewati.\n";
        continue;
    }
    $count = 0;
    while ($cursor <= $end) {
        $insert->execute([$row['id'], date('Y-m-d', $cursor)]);
        $count += $insert->rowCount();
        $cursor = strtotime('+1 day', $cursor);
    }
    if ($count > 0) {
        echo "  - Leave #{$row['id']} ({$row['tanggal_mulai']} s/d {$row['tanggal_selesai']}): {$count} tanggal ditambahkan.\n";
    }
    $totalInserted += $count;
}

echo "\nSelesai. Total baris leave_request_dates yang ditambahkan: {$totalInserted}.\n";
