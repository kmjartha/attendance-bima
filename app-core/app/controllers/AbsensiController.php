<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\App;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserShift;
use App\Models\Shift;
use App\Models\Announcement;
use App\Models\RolePolicy;
use App\Models\Holiday;

class AbsensiController extends Controller
{
    private function forbidSupervisorSubmit(): string
    {
        if (!has_role('Supervisor')) return '';
        http_response_code(403);
        return $this->render('errors.403', ['title' => '403'], 'auth');
    }

    /** GET /absensi — form selfie + GPS */
    public function form(): string
    {
        if ($resp = $this->forbidSupervisorSubmit()) return $resp;
        $u          = user();
        $userModel  = new User();
        $full       = $userModel->findWithRole((int)$u['id']);
        $att        = (new Attendance())->todayFor((int)$u['id']);

        $userShiftModel = new UserShift();
        $todayShift = $userShiftModel->shiftForDate((int)$u['id'], date('Y-m-d'));
        $shiftId = $todayShift['id'] ?? $userShiftModel->defaultShiftId((int)$u['id']);
        $shift   = $shiftId ? (new Shift())->find($shiftId) : null;
        $userShifts = $userShiftModel->shiftsFor((int)$u['id']);

        $hasFace = !empty($full['face_descriptor']);
        $mode    = ($att && $att['jam_masuk'] && !$att['jam_keluar']) ? 'keluar' : 'masuk';
        if ($att && $att['jam_masuk'] && $att['jam_keluar']) {
            $mode = 'done';
        }

        $layout = is_pegawai() ? 'mobile' : 'app';

        // Kalau hari ini bukan hari kerja efektif (hari_aktif shift, atau
        // fallback weekend role) / hari libur, dan belum absen masuk -> jangan
        // tampilkan form kamera, tampilkan layar "Selamat Liburan" saja.
        if ($mode === 'masuk') {
            // Sama seperti di submit(): kalau shift default-nya overnight
            // (mis. Malam 23:00->07:00) dan sekarang masih dini hari sebelum
            // jam keluar, tanggal yang relevan adalah KEMARIN, bukan hari
            // kalender sekarang. Menjaga konsistensi dgn submit() supaya
            // form yang ditampilkan tidak beda keputusan dgn yang divalidasi
            // saat submit.
            $tanggalCek = date('Y-m-d');
            if ($shift) {
                $sm = strtotime($shift['jam_masuk']);
                $sk = strtotime($shift['jam_keluar']);
                if ($sk <= $sm && strtotime(date('H:i:s')) < $sk) {
                    $tanggalCek = date('Y-m-d', strtotime('-1 day'));
                }
            }
            $holiday = (new Holiday())->findBy('tanggal', $tanggalCek);
            if ($holiday) {
                return $this->render('absensi.libur', [
                    'title'   => 'Hari Libur',
                    'holiday' => $holiday,
                ], $layout);
            }
        }

        return $this->render('absensi.form', [
            'title'       => $mode === 'keluar' ? 'Absen Pulang' : 'Absen Masuk',
            'me'          => $full,
            'today'       => $att,
            'shift'       => $shift,
            'user_shifts' => $userShifts,
            'mode'        => $mode,
            'has_face'    => $hasFace,
            'face_thresh' => App::$config['face']['distance_threshold'] ?? 0.60,
        ], $layout);
    }

    /** POST /absensi/submit — AJAX */
    public function submit(): string
    {
        if ($resp = $this->forbidSupervisorSubmit()) return $resp;
        header('Content-Type: application/json');

        $u          = user();
        $userModel  = new User();
        $me         = $userModel->find((int)$u['id']);
        if (!$me) return $this->json(['success'=>false,'message'=>'User tidak ditemukan'], 404);

        $type        = $_POST['type'] ?? 'masuk';   // masuk | keluar
        $foto        = $_POST['foto'] ?? '';
        $lat         = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng         = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $clientDist  = isset($_POST['face_distance']) ? (float)$_POST['face_distance'] : null;
        $clientDesc  = $_POST['descriptor'] ?? null; // JSON

        if (!in_array($type, ['masuk','keluar'], true)) {
            return $this->json(['success'=>false,'message'=>'Tipe absensi tidak valid']);
        }
        if ($lat === null || $lng === null) {
            return $this->json(['success'=>false,'message'=>'Lokasi GPS tidak terdeteksi']);
        }
        if (!$foto) {
            return $this->json(['success'=>false,'message'=>'Foto selfie tidak ditemukan']);
        }

        // Validasi GPS radius (Haversine)
        $jarak = haversine_meters((float)$me['latitude_kantor'], (float)$me['longitude_kantor'], $lat, $lng);
        if ($jarak > (int)$me['radius_meter']) {
            return $this->json([
                'success' => false,
                'message' => 'Anda berada di luar area kantor (jarak ' . number_format($jarak, 0) . ' m, maks ' . (int)$me['radius_meter'] . ' m).',
            ]);
        }

        // Validasi face match (server-side double-check bila descriptor live dikirim)
        $threshold = (float)(App::$config['face']['distance_threshold'] ?? 0.60);
        $serverDist = null;
        if ($me['face_descriptor'] && $clientDesc) {
            $live = json_decode($clientDesc, true);
            $stored = face_decode($me['face_descriptor']);
            if (is_array($live) && $stored) {
                $serverDist = face_distance($stored, array_map('floatval', $live));
            }
        }
        $finalDist = $serverDist ?? $clientDist;
        if ($me['face_descriptor']) {
            if ($finalDist === null) {
                return $this->json(['success'=>false,'message'=>'Wajah tidak terdeteksi. Posisikan wajah di tengah kamera.']);
            }
            if ($finalDist > $threshold) {
                return $this->json([
                    'success' => false,
                    'message' => 'Wajah tidak cocok dengan data terdaftar (skor ' . number_format(face_match_score($finalDist), 1) . '%).',
                ]);
            }
        }
        $score = $finalDist !== null ? face_match_score($finalDist) : null;

        // Save image
        $maxBytes = (int)(App::$config['upload']['attendance_max'] ?? 3 * 1024 * 1024);
        $rel = save_base64_image($foto, 'attendance', $maxBytes);
        if (!$rel) {
            return $this->json(['success'=>false,'message'=>'Foto selfie tidak valid atau terlalu besar']);
        }

        $attModel = new Attendance();
        $today    = $attModel->todayFor((int)$me['id']);

        // Shift aktif / dipilih
        $userShiftModel = new UserShift();
        $allowedShiftIds = $userShiftModel->shiftIdsFor((int)$me['id']);
        $selectedShiftId = isset($_POST['shift_id']) ? (int)$_POST['shift_id'] : null;
        if ($selectedShiftId && !in_array($selectedShiftId, $allowedShiftIds, true)) {
            return $this->json(['success'=>false,'message'=>'Shift yang dipilih tidak valid']);
        }
        $dateShift = $userShiftModel->shiftForDate((int)$me['id'], date('Y-m-d'));
        $defaultShiftId = $dateShift['id'] ?? null;
        $shiftId = $selectedShiftId && in_array($selectedShiftId, $allowedShiftIds, true)
            ? $selectedShiftId
            : $defaultShiftId;
        $shift   = $shiftId ? (new Shift())->find($shiftId) : null;

        $now = current_time();

        if ($type === 'masuk') {
            if ($today && $today['jam_masuk']) {
                return $this->json(['success'=>false,'message'=>'Anda sudah absen masuk hari ini']);
            }

            // Tentukan tanggal shift yang sebenarnya SEBELUM cek hari libur/hari
            // efektif di bawah ini. Untuk shift yang melewati tengah malam (mis.
            // Malam 23:00 -> 07:00), kalau karyawan absen masuk di dini hari
            // (misal jam 00:30) SETELAH tengah malam tapi MASIH SEBELUM jam
            // keluar shift tsb, itu tetap bagian dari shift yang dimulai
            // KEMARIN — bukan shift baru hari ini. Kalau cek hari libur di
            // bawah ini pakai tanggal kalender hari ini (bukan tanggal shift
            // yang benar), bisa salah menolak/menerima absen tepat di seputar
            // tengah malam.
            $tanggalAbsen = date('Y-m-d');
            if ($shift) {
                $shiftMasukTime  = strtotime($shift['jam_masuk']);
                $shiftKeluarTime = strtotime($shift['jam_keluar']);
                $isOvernight     = $shiftKeluarTime <= $shiftMasukTime;
                if ($isOvernight && strtotime(date('H:i:s')) < $shiftKeluarTime) {
                    $tanggalAbsen = date('Y-m-d', strtotime('-1 day'));
                }
            }

            // Hanya hari libur yang diatur di aplikasi yang memblokir absen masuk.
            $rolePolicyOffDay = (new RolePolicy())->forRole((int)$me['role_id']);
            $holidayToday = (new Holiday())->findBy('tanggal', $tanggalAbsen);
            if ($holidayToday) {
                return $this->json(['success'=>false,'message'=>'Hari ini adalah hari libur yang telah ditetapkan di aplikasi. Selamat liburan!']);
            }

            // Tutup absen masuk di jam malam utk role yang kebijakannya
            // mengatur begitu (mis. Guru/Staff tidak boleh absen masuk
            // lewat jam 23:00 s/d dini hari). Role dgn allow_overnight
            // (Satpam shift malam) dikecualikan total dari pengecekan ini.
            $rolePolicy = $rolePolicyOffDay;
            if (is_checkin_blocked_now($rolePolicy)) {
                $batasJam = substr((string)($rolePolicy['checkin_block_time'] ?? '23:00:00'), 0, 5);
                return $this->json([
                    'success' => false,
                    'message' => "Absen masuk untuk role Anda ditutup pukul {$batasJam} s/d dini hari. Hubungi HRD jika ada kendala.",
                ]);
            }

            // Status: hadir / telat
            $status = 'hadir';
            if ($shift) {
                $jamMasukShift = strtotime($tanggalAbsen . ' ' . $shift['jam_masuk']);
                $batas = $jamMasukShift + ((int)$shift['toleransi_menit']) * 60;
                if (time() > $batas) $status = 'telat';
            }
            $reason = trim($_POST['keterangan'] ?? '');
            if ($status === 'telat' && $reason === '') {
                return $this->json(['success'=>false,'message'=>'Alasan terlambat harus diisi.']);
            }
            $data = [
                'user_id'          => $me['id'],
                'shift_id'         => $shiftId,
                'tanggal'          => $tanggalAbsen,
                'jam_masuk'        => $now,
                'foto_masuk'       => $rel,
                'lat_masuk'        => $lat,
                'lng_masuk'        => $lng,
                'face_match_masuk' => $score,
                'status'           => $status,
                'keterangan'       => $reason ?: null,
            ];
            $attModel->create($data);
            return $this->json([
                'success' => true,
                'message' => 'Absen masuk berhasil. Status: ' . strtoupper($status) . '.',
                'redirect'=> url('/absensi/riwayat'),
            ]);
        }

        // type=keluar
        if (!$today || !$today['jam_masuk']) {
            return $this->json(['success'=>false,'message'=>'Anda belum absen masuk hari ini']);
        }
        if ($today['jam_keluar']) {
            return $this->json(['success'=>false,'message'=>'Anda sudah absen pulang hari ini']);
        }
        if (strtotime($now) <= strtotime($today['jam_masuk'])) {
            return $this->json(['success'=>false,'message'=>'Jam pulang harus lebih besar dari jam masuk']);
        }
        $attModel->update((int)$today['id'], [
            'jam_keluar'        => $now,
            'foto_keluar'       => $rel,
            'lat_keluar'        => $lat,
            'lng_keluar'        => $lng,
            'face_match_keluar' => $score,
        ]);
        return $this->json([
            'success' => true,
            'message' => 'Absen pulang berhasil. Selamat istirahat 👋',
            'redirect'=> url('/absensi/riwayat'),
        ]);
    }

    /** GET /absensi/riwayat */
    public function riwayat(): string
    {
        $u     = user();
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        if ($month < 1 || $month > 12) $month = (int)date('n');
        if ($year  < 2020 || $year  > 2100) $year = (int)date('Y');

        $rows = (new Attendance())->history((int)$u['id'], $month, $year);
        $summary = (new Attendance())->summaryMonth((int)$u['id'], $month, $year);

        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('absensi.riwayat', [
            'title'   => 'Riwayat Absensi',
            'rows'    => $rows,
            'month'   => $month,
            'year'    => $year,
            'summary' => $summary,
        ], $layout);
    }
}
