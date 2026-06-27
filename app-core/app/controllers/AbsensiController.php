<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\App;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserShift;
use App\Models\Shift;
use App\Models\Announcement;

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
        $shiftId = $userShiftModel->defaultShiftId((int)$u['id']);
        $shift   = $shiftId ? (new Shift())->find($shiftId) : null;
        $userShifts = $userShiftModel->shiftsFor((int)$u['id']);

        $hasFace = !empty($full['face_descriptor']);
        $mode    = ($att && $att['jam_masuk'] && !$att['jam_keluar']) ? 'keluar' : 'masuk';
        if ($att && $att['jam_masuk'] && $att['jam_keluar']) {
            $mode = 'done';
        }

        $layout = is_pegawai() ? 'mobile' : 'app';

        return $this->render('absensi.form', [
            'title'       => $mode === 'keluar' ? 'Absen Pulang' : 'Absen Masuk',
            'me'          => $full,
            'today'       => $att,
            'shift'       => $shift,
            'user_shifts' => $userShifts,
            'mode'        => $mode,
            'has_face'    => $hasFace,
            'face_thresh' => App::$config['face']['distance_threshold'] ?? 0.50,
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
        $threshold = (float)(App::$config['face']['distance_threshold'] ?? 0.50);
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
        $shiftId = $selectedShiftId
            ? $selectedShiftId
            : $userShiftModel->defaultShiftId((int)$me['id']);
        $shift   = $shiftId ? (new Shift())->find($shiftId) : null;

        $now = current_time();

        if ($type === 'masuk') {
            if ($today && $today['jam_masuk']) {
                return $this->json(['success'=>false,'message'=>'Anda sudah absen masuk hari ini']);
            }
            // Status: hadir / telat
            $status = 'hadir';
            if ($shift) {
                $jamMasukShift = strtotime(date('Y-m-d') . ' ' . $shift['jam_masuk']);
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
                'tanggal'          => date('Y-m-d'),
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
