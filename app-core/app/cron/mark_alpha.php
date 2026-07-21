<?php
/**
 * CRON — Tandai ALPHA otomatis.
 * ---------------------------------------------------------------------
 * Sebelum ini kolom status 'alpha' sudah ada di skema database dan di
 * SEMUA query laporan (Attendance::rekapPeriode, summaryMonth, dst),
 * tapi tidak ada satupun proses yang benar-benar menulis baris
 * berstatus alpha — baris `attendances` hanya pernah dibuat saat user
 * absen masuk sendiri. Makanya kolom "Alpha" di laporan selalu 0 walau
 * kenyataannya ada karyawan yang tidak pernah absen. Script inilah yang
 * mengisi kekosongan itu: dijalankan otomatis tiap hari lewat cron,
 * memeriksa siapa saja yang seharusnya masuk kerja kemarin tapi tidak
 * pernah absen, lalu menulis baris `alpha` untuk mereka.
 *
 * AMAN dijalankan berkali-kali (idempotent) — baris yang sudah ada
 * tidak akan dibuat ulang / ditimpa.
 *
 * ---------------------------------------------------------------------
 * CARA PASANG DI HOSTINGER (hPanel > Advanced > Cron Jobs):
 *   Command : /usr/bin/php /home/USERNAME/domains/DOMAIN-ANDA/public_html/app-core/app/cron/mark_alpha.php
 *   Jadwal  : setiap hari jam 01:05  →  cron expression: 5 1 * * *
 *   (Sesuaikan path di atas dengan lokasi folder app-core di hosting Anda.
 *    Cek di hPanel > File Manager kalau tidak yakin path lengkapnya.)
 *
 * KENAPA JAM 01:05, BUKAN TENGAH MALAM PERSIS ATAU SORE HARI?
 * Script ini SELALU mengevaluasi tanggal KEMARIN (bukan hari ini
 * berjalan). Ini penting untuk shift malam (mis. Satpam, shift 23:00–
 * 07:00): kalau script dijalankan terlalu awal, shift yang baru saja
 * mulai belum tentu sudah "telat" menurut batas jam kebijakannya
 * sendiri. Dengan mengevaluasi tanggal kemarin di jam 01:05 dini hari,
 * seluruh shift (termasuk shift malam) sudah pasti lewat batas jamnya
 * masing-masing sebelum divonis alpha.
 * ---------------------------------------------------------------------
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script ini hanya boleh dijalankan lewat CLI/cron, bukan browser.\n");
}

require_once dirname(__DIR__, 2) . '/config/env.php';

define('BASE_PATH', dirname(__DIR__, 2));       // .../app-core
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', dirname(BASE_PATH));       // document root (public_html)
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
            $relative = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
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

$targetDate = date('Y-m-d', strtotime('-1 day'));
echo "[mark_alpha] Evaluasi tanggal: {$targetDate} (" . indo_day_name($targetDate) . ")\n";

$stats = (new Attendance())->runAlphaMarking($targetDate);

echo "[mark_alpha] Selesai. Ditandai alpha: {$stats['marked']}. "
   . "Dilewati - sudah ada baris: {$stats['skipped_existing']}, sedang cuti/sakit: {$stats['skipped_leave']}, "
   . "bukan hari efektif/libur (dikecualikan): {$stats['skipped_exempt']}, belum lewat batas jam: {$stats['skipped_too_early']}.\n";
