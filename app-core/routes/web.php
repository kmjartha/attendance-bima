<?php
/**
 * Definisi route — dipanggil dari public/index.php.
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

/** @var \App\Core\Router $router */

// Root
$router->get('/', function () {
    header('Location: ' . url(auth_check() ? '/dashboard' : '/login'));
    return '';
});

// Auth
$router->get('/login',  'AuthController@showLogin');
$router->post('/login', 'AuthController@login', [CsrfMiddleware::class]);
$router->any('/logout', 'AuthController@logout', [AuthMiddleware::class]);

// Dashboard
$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);
$router->get('/profile', 'AuthController@profile', [AuthMiddleware::class]);
$router->post('/profile/password', 'AuthController@updatePassword', [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Master Karyawan (HRD) =========
$router->get ('/karyawan',              'KaryawanController@index',   [AuthMiddleware::class]);
$router->get ('/karyawan/create',       'KaryawanController@create',  [AuthMiddleware::class]);
$router->post('/karyawan/create',       'KaryawanController@store',   [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get ('/karyawan/{id}',         'KaryawanController@show',    [AuthMiddleware::class]);
$router->get ('/karyawan/{id}/edit',    'KaryawanController@edit',    [AuthMiddleware::class]);
$router->post('/karyawan/{id}/edit',    'KaryawanController@update',  [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/karyawan/{id}/delete',  'KaryawanController@destroy', [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Master Shift (HRD) =========
$router->get ('/shift',                'ShiftController@index',   [AuthMiddleware::class]);
$router->post('/shift/create',         'ShiftController@store',   [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/shift/{id}/edit',      'ShiftController@update',  [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/shift/{id}/delete',    'ShiftController@destroy', [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Pengumuman (HRD/Kepsek) =========
$router->get ('/pengumuman',                'PengumumanController@index',   [AuthMiddleware::class]);
$router->get ('/pengumuman/create',         'PengumumanController@create',  [AuthMiddleware::class]);
$router->post('/pengumuman/create',         'PengumumanController@store',   [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get ('/pengumuman/{id}/edit',      'PengumumanController@edit',    [AuthMiddleware::class]);
$router->post('/pengumuman/{id}/edit',      'PengumumanController@update',  [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/pengumuman/{id}/toggle',    'PengumumanController@toggle',  [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/pengumuman/{id}/delete',    'PengumumanController@destroy', [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Absensi (semua user login) =========
$router->get ("/absensi",          "AbsensiController@form",    [AuthMiddleware::class]);
$router->post("/absensi/submit",   "AbsensiController@submit",  [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get ("/absensi/riwayat",  "AbsensiController@riwayat", [AuthMiddleware::class]);

// ========= Cuti / Sakit (semua user) =========
$router->get ("/cuti",            "CutiController@index",  [AuthMiddleware::class]);
$router->get ("/cuti/create",     "CutiController@create", [AuthMiddleware::class]);
$router->post("/cuti/create",     "CutiController@store",  [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Verifikasi Cuti (HRD/Kepsek) =========
$router->get ("/verifikasi-cuti",                "VerifikasiController@index",  [AuthMiddleware::class]);
$router->post("/verifikasi-cuti/{id}/action",    "VerifikasiController@action", [AuthMiddleware::class, CsrfMiddleware::class]);

// ========= Laporan =========
$router->get ("/laporan",                       "LaporanController@index",          [AuthMiddleware::class]);
$router->get ("/laporan/personal",              "LaporanController@personal",       [AuthMiddleware::class]);
$router->get ("/laporan/general",               "LaporanController@general",        [AuthMiddleware::class]);
$router->get ("/laporan/karyawan",              "LaporanController@karyawan",       [AuthMiddleware::class]);
$router->get ("/laporan/karyawan/{id}",         "LaporanController@karyawanDetail", [AuthMiddleware::class]);
$router->get ("/laporan/harian",                "LaporanController@harian",        [AuthMiddleware::class]);
$router->get ("/laporan/karyawan/{id}/export",  "LaporanController@karyawanExport", [AuthMiddleware::class]);
$router->post("/laporan/harian/user/{userId}/edit", "LaporanController@saveAttendance", [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post("/laporan/harian/{id}/delete",     "LaporanController@deleteAttendance", [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get ("/laporan/export",                "LaporanController@export",         [AuthMiddleware::class]);

// ========= Notifikasi (semua user) =========
$router->get ("/notifikasi",                     "NotifikasiController@index",   [AuthMiddleware::class]);
$router->get ("/notifikasi/{type}/{id}",        "NotifikasiController@show",    [AuthMiddleware::class]);
$router->post("/notifikasi/read-all",            "NotifikasiController@readAll", [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post("/notifikasi/read",                "NotifikasiController@read",    [AuthMiddleware::class, CsrfMiddleware::class]);

