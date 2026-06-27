<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;

class LaporanController extends Controller
{
    /** GET /laporan — auto-pilih general (HRD/Kepsek) atau personal (pegawai) */
    public function index(): string
    {
        if (has_role('HRD','Kepsek')) {
            return $this->general();
        }
        if (is_pegawai()) {
            return $this->personal();
        }
        http_response_code(403);
        return $this->render('errors.403', ['title'=>'403'], 'auth');
    }

    /** General rekap (HRD/Kepsek) */
    public function general(): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $rows  = (new Attendance())->rekapPeriode($month, $year);

        return $this->render('laporan.general', [
            'title' => 'Laporan Rekap Absensi',
            'rows'  => $rows,
            'month' => $month,
            'year'  => $year,
        ]);
    }

    /** GET /laporan/karyawan — daftar karyawan + ringkasan utk drill-down */
    public function karyawan(): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $q     = trim((string)($_GET['q'] ?? ''));
        $role  = trim((string)($_GET['role'] ?? ''));

        $rows = (new Attendance())->rekapPeriode($month, $year);
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, fn($r) =>
                str_contains(mb_strtolower($r['nama']), $needle) ||
                str_contains(mb_strtolower($r['niy']),  $needle)
            ));
        }
        if ($role !== '') {
            $rows = array_values(array_filter($rows, fn($r) => $r['role_name'] === $role));
        }

        return $this->render('laporan.karyawan', [
            'title' => 'Laporan Karyawan',
            'rows'  => $rows,
            'month' => $month,
            'year'  => $year,
            'q'     => $q,
            'role'  => $role,
        ]);
    }

    /** GET /laporan/karyawan/{id} — detail per karyawan utk periode */
    public function karyawanDetail(int $id): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $userModel = new User();
        $karyawan  = $userModel->findWithRole($id);
        if (!$karyawan) {
            http_response_code(404);
            return $this->render('errors.404', ['title'=>'404'], 'auth');
        }
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        $att     = new Attendance();
        $summary = $att->summaryMonth($id, $month, $year);
        $history = $att->history($id, $month, $year);

        // Daily series untuk Chart.js
        $byDay = [];
        foreach ($history as $h) {
            $byDay[(int)date('j', strtotime($h['tanggal']))] = $h['status'];
        }
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        $labels = $hadirSeries = $telatSeries = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[]      = (string)$d;
            $st = $byDay[$d] ?? '';
            $hadirSeries[] = $st === 'hadir' ? 1 : 0;
            $telatSeries[] = $st === 'telat' ? 1 : 0;
        }

        return $this->render('laporan.karyawan_detail', [
            'title'       => 'Laporan: ' . $karyawan['nama'],
            'karyawan'    => $karyawan,
            'month'       => $month,
            'year'        => $year,
            'summary'     => $summary,
            'history'     => $history,
            'labels'      => $labels,
            'hadirSeries' => $hadirSeries,
            'telatSeries' => $telatSeries,
        ]);
    }

    /** GET /laporan/harian — daftar absensi per tanggal (HRD) */
    public function harian(): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $date = trim((string)($_GET['date'] ?? date('Y-m-d')));
        $q = trim((string)($_GET['q'] ?? ''));
        $att = new Attendance();
        $rows = $att->dailyReport($date);

        // Filter by search query
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, fn($r) =>
                str_contains(mb_strtolower($r['nama']), $needle) ||
                str_contains(mb_strtolower($r['niy']), $needle)
            ));
        }

        return $this->render('laporan.harian', [
            'title' => 'Laporan Harian',
            'date'  => $date,
            'rows'  => $rows,
            'q'     => $q,
        ]);
    }

    public function saveAttendance(int $userId): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }

        $date = trim((string)($_POST['date'] ?? ''));
        $jamMasuk = trim((string)($_POST['jam_masuk'] ?? ''));
        $jamKeluar = trim((string)($_POST['jam_keluar'] ?? ''));
        $attendanceId = (int)($_POST['attendance_id'] ?? 0);

        if ($date === '') {
            $this->flash('error', 'Tanggal wajib dipilih.');
            return $this->redirect('/laporan/harian');
        }

        $att = new Attendance();
        $existing = $attendanceId ? $att->find($attendanceId) : null;

        $shiftId = (new UserShift())->defaultShiftId($userId);
        $shift = $shiftId ? (new Shift())->find($shiftId) : null;
        $status = 'hadir';
        if ($shift && !empty($shift['jam_masuk'])) {
            $lateMinutes = max(0, (strtotime("{$date} {$jamMasuk}") - strtotime("{$date} {$shift['jam_masuk']}")) / 60 - (int)$shift['toleransi_menit']);
            $status = $lateMinutes > 0 ? 'telat' : 'hadir';
        }

        $data = [
            'user_id' => $userId,
            'shift_id' => $shiftId,
            'tanggal' => $date,
            'jam_masuk' => $jamMasuk !== '' ? "{$date} {$jamMasuk}" : null,
            'jam_keluar' => $jamKeluar !== '' ? "{$date} {$jamKeluar}" : null,
            'status' => $status,
        ];

        if ($existing) {
            $att->update($existing['id'], $data);
            $this->flash('success', 'Kehadiran berhasil diperbarui.');
        } else {
            $att->create($data);
            $this->flash('success', 'Kehadiran berhasil ditambahkan.');
        }

        return $this->redirect('/laporan/harian?date=' . urlencode($date));
    }

    public function deleteAttendance(int $id): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }

        $att = new Attendance();
        $row = $att->find($id);
        if (!$row) {
            $this->flash('error', 'Data kehadiran tidak ditemukan.');
            return $this->redirect('/laporan/harian');
        }

        $date = $row['tanggal'];
        $att->delete($id);
        $this->flash('success', 'Kehadiran berhasil dihapus.');
        return $this->redirect('/laporan/harian?date=' . urlencode($date));
    }

    /** Personal pegawai */
    public function personal(): string
    {
        $u     = user();
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $att   = new Attendance();
        $sum   = $att->summaryMonth((int)$u['id'], $month, $year);
        $hist  = $att->history((int)$u['id'], $month, $year);

        $byDay = [];
        foreach ($hist as $h) $byDay[(int)date('j', strtotime($h['tanggal']))] = $h['status'];
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        $labels = [];  $hadirSeries = [];
        for ($d=1; $d<=$daysInMonth; $d++) {
            $labels[]      = (string)$d;
            $hadirSeries[] = in_array($byDay[$d] ?? '', ['hadir','telat'], true) ? 1 : 0;
        }

        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('laporan.personal', [
            'title'   => 'Laporan Pribadi',
            'month'   => $month,
            'year'    => $year,
            'summary' => $sum,
            'labels'  => $labels,
            'hadirSeries' => $hadirSeries,
        ], $layout);
    }

    /** GET /laporan/export — Excel rekap general via HTML table */
    public function export(): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $q     = trim((string)($_GET['q'] ?? ''));
        $role  = trim((string)($_GET['role'] ?? ''));
        $rows  = (new Attendance())->rekapPeriode($month, $year);

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, fn($r) =>
                str_contains(mb_strtolower($r['nama']), $needle) ||
                str_contains(mb_strtolower($r['niy']), $needle)
            ));
        }
        if ($role !== '') {
            $rows = array_values(array_filter($rows, fn($r) => $r['role_name'] === $role));
        }

        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $fname = "Laporan_Absensi_{$bulan[$month]}_{$year}.xls";
        $this->excelHeaders($fname);

        $html  = $this->excelStyles();
        $html .= "<h2>Laporan Rekap Absensi — {$bulan[$month]} {$year}</h2>";
        $html .= "<p>Dicetak: " . date('d-m-Y H:i') . " · Total karyawan aktif: " . count($rows) . "</p>";
        $html .= '<table><thead><tr>'
               . '<th>NIY</th><th>Nama</th><th>Role</th>'
               . '<th>Hadir</th><th>Telat</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th>'
               . '</tr></thead><tbody>';
        $tot = ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0,'total'=>0];
        foreach ($rows as $r) {
            foreach (array_keys($tot) as $k) $tot[$k] += (int)$r[$k];
            $html .= '<tr>'
                   . '<td>'.htmlspecialchars($r['niy']).'</td>'
                   . '<td>'.htmlspecialchars($r['nama']).'</td>'
                   . '<td>'.htmlspecialchars($r['role_name']).'</td>'
                   . '<td>'.(int)$r['hadir'].'</td>'
                   . '<td>'.(int)$r['telat'].'</td>'
                   . '<td>'.(int)$r['izin'].'</td>'
                   . '<td>'.(int)$r['sakit'].'</td>'
                   . '<td>'.(int)$r['alpha'].'</td>'
                   . '<td>'.(int)$r['total'].'</td>'
                   . '</tr>';
        }
        $html .= '</tbody><tfoot><tr><td colspan="3">TOTAL</td>'
               . '<td>'.$tot['hadir'].'</td><td>'.$tot['telat'].'</td>'
               . '<td>'.$tot['izin'].'</td><td>'.$tot['sakit'].'</td>'
               . '<td>'.$tot['alpha'].'</td><td>'.$tot['total'].'</td></tr></tfoot></table>';
        $html .= '</body></html>';
        echo $html;
        return '';
    }

    /** GET /laporan/karyawan/{id}/export — Excel detail per karyawan */
    public function karyawanExport(int $id): string
    {
        if (!has_role('HRD','Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title'=>'403'], 'auth');
        }
        $karyawan = (new User())->findWithRole($id);
        if (!$karyawan) { http_response_code(404); return ''; }

        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $att   = new Attendance();
        $sum   = $att->summaryMonth($id, $month, $year);
        $hist  = $att->history($id, $month, $year);

        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $fname = "Laporan_{$karyawan['niy']}_{$bulan[$month]}_{$year}.xls";
        $this->excelHeaders($fname);

        $html  = $this->excelStyles();
        $html .= "<h2>Laporan Absensi — " . htmlspecialchars($karyawan['nama']) . "</h2>";
        $html .= "<p>NIY: <b>" . htmlspecialchars($karyawan['niy']) . "</b> · Role: " . htmlspecialchars($karyawan['role_name'])
              . " · Periode: <b>{$bulan[$month]} {$year}</b></p>";

        $html .= '<h3>Ringkasan</h3><table><thead><tr>'
              . '<th>Hadir</th><th>Telat</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th>'
              . '</tr></thead><tbody><tr>'
              . '<td>'.(int)$sum['hadir'].'</td>'
              . '<td>'.(int)$sum['telat'].'</td>'
              . '<td>'.(int)$sum['izin'].'</td>'
              . '<td>'.(int)$sum['sakit'].'</td>'
              . '<td>'.(int)$sum['alpha'].'</td>'
              . '<td>'.array_sum(array_map('intval', $sum)).'</td>'
              . '</tr></tbody></table>';

          $html .= '<h3>Detail Harian</h3><table><thead><tr>'
              . '<th>Tanggal</th><th>Shift</th><th>Jam Masuk</th><th>Menit Telat</th><th>Jam Keluar</th>'
              . '<th>Status</th><th>Match Score</th>'
              . '</tr></thead><tbody>';
        foreach ($hist as $h) {
            $html .= '<tr>'
                  . '<td>'.htmlspecialchars($h['tanggal']).'</td>'
                  . '<td>'.htmlspecialchars((string)($h['shift_nama'] ?? '-')).'</td>'
                . '<td>'.htmlspecialchars((string)($h['jam_masuk'] ?? '-')).'</td>'
                . '<td>'.(isset($h['terlambat_menit']) && $h['terlambat_menit']!==null ? (int)$h['terlambat_menit'] : '-').'</td>'
                . '<td>'.htmlspecialchars((string)($h['jam_keluar'] ?? '-')).'</td>'
                  . '<td>'.htmlspecialchars(strtoupper($h['status'])).'</td>'
                  . '<td>'.(isset($h['face_match_score']) && $h['face_match_score']!==null
                            ? number_format((float)$h['face_match_score'],3) : '-').'</td>'
                  . '</tr>';
        }
        if (!$hist) $html .= '<tr><td colspan="6">Tidak ada data absensi pada periode ini.</td></tr>';
        $html .= '</tbody></table></body></html>';
        echo $html;
        return '';
    }

    private function excelHeaders(string $fname): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Cache-Control: max-age=0');
    }

    private function excelStyles(): string
    {
        return '<html><head><meta charset="utf-8"><style>'
             . 'body{font-family:Arial,sans-serif}'
             . 'h2{margin:0 0 4px} h3{margin:18px 0 6px}'
             . 'table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;margin-bottom:14px}'
             . 'th,td{border:1px solid #999;padding:6px 10px}'
             . 'th{background:#2563eb;color:#fff;text-align:left}'
             . 'tfoot td{background:#ecfdf5;font-weight:bold}'
             . '</style></head><body>';
    }
}
