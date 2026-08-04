<?php

namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{
    protected string $table = 'attendances';

    /**
     * Sinkronkan satu pengajuan cuti/sakit yang SUDAH disetujui ke tabel
     * attendances, supaya kolom "Izin"/"Sakit" di laporan benar-benar
     * terisi (sebelumnya kolom ini SELALU 0 -- ada di query laporan tapi
     * tidak ada satupun proses yang pernah menulis baris izin/sakit,
     * persis seperti masalah alpha yang dulu tidak pernah ditulis).
     *
     * Dipanggil dari 2 tempat: VerifikasiController saat approve cuti
     * baru, dan app/cron/backfill_leave_attendance.php utk cuti yang
     * SUDAH lama disetujui sebelum perbaikan ini ada.
     *
     * Aturan: hanya menulis utk tanggal yang memang hari kerja efektif
     * karyawan tsb (skip weekend/libur yang tidak relevan). Kalau ada
     * baris attendances yang sudah ada, kami perbarui menjadi status cuti
     * yang disetujui supaya laporan konsisten dengan pengajuan cuti yang
     * sedang diapprove, termasuk saat ada data 'hadir'/'telat'/'alpha'
     * yang sebelumnya tersisa.
     *
     * @return int jumlah baris yang ditulis/diperbarui
     */
    public function syncFromApprovedLeave(array $leave): int
    {
        $userId = (int)$leave['user_id'];
        $status = $leave['jenis'] === 'sakit' ? 'sakit' : 'izin';
        $label  = 'Cuti ' . ($leave['jenis'] === 'sakit' ? 'sakit' : $leave['jenis']) . ' disetujui';

        $userModel      = new User();
        $policyModel    = new RolePolicy();
        $userShiftModel = new UserShift();
        $holidayModel   = new Holiday();

        $user       = $userModel->find($userId);
        if (!$user) return 0;
        $policy     = $policyModel->forRole((int)$user['role_id']);
        $assignment = $userShiftModel->defaultAssignment($userId);

        $written = 0;
        $cursor  = strtotime($leave['tanggal_mulai']);
        $end     = strtotime($leave['tanggal_selesai']);
        if ($cursor === false || $end === false || $cursor > $end) return 0;

        while ($cursor <= $end) {
            $date    = date('Y-m-d', $cursor);
            $holiday = $holidayModel->findBy('tanggal', $date);

            if (!is_exempt_from_alpha($policy, $assignment, $date, $holiday)) {
                $existing = $this->findByUserDate($userId, $date);
                if (!$existing) {
                    $this->create([
                        'user_id'    => $userId,
                        'shift_id'   => $assignment['shift_id'] ?? null,
                        'tanggal'    => $date,
                        'status'     => $status,
                        'keterangan' => $label,
                    ]);
                    $written++;
                } elseif ($existing['status'] !== $status || ($existing['keterangan'] ?? '') !== $label) {
                    $this->update((int)$existing['id'], ['status' => $status, 'keterangan' => $label]);
                    $written++;
                }
            }

            $cursor = strtotime('+1 day', $cursor);
        }

        return $written;
    }

    public function deleteLeaveRowsForApprovedLeave(array $leave): int
    {
        $userId = (int)$leave['user_id'];
        $cursor = strtotime($leave['tanggal_mulai']);
        $end    = strtotime($leave['tanggal_selesai']);
        if ($cursor === false || $end === false || $cursor > $end) {
            return 0;
        }

        $label = 'Cuti ' . ($leave['jenis'] === 'sakit' ? 'sakit' : $leave['jenis']) . ' disetujui';
        $stmt  = $this->db()->prepare(
            "DELETE FROM {$this->table} WHERE user_id = ? AND tanggal = ? " .
            "AND status IN ('izin','sakit') AND keterangan = ?"
        );

        $deleted = 0;
        while ($cursor <= $end) {
            $stmt->execute([$userId, date('Y-m-d', $cursor), $label]);
            $deleted += $stmt->rowCount();
            $cursor = strtotime('+1 day', $cursor);
        }

        return $deleted;
    }

    public function findByUserDate(int $userId, string $date): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM attendances WHERE user_id = ? AND tanggal = ? LIMIT 1");
        $stmt->execute([$userId, $date]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Jalankan pengecekan & penandaan alpha utk SATU tanggal tertentu.
     * Logic inti ini dipakai bareng oleh app/cron/mark_alpha.php (jalan
     * otomatis tiap dini hari, evaluasi tanggal KEMARIN) dan tombol
     * manual "Jalankan Cek Alpha Sekarang" di menu HRD (evaluasi HARI INI
     * atau tanggal pilihan HRD, utk testing/situasi cron belum sempat
     * terpasang di hosting).
     *
     * AMAN dipakai utk tanggal manapun termasuk hari ini yang masih
     * berjalan -- karyawan yang jam batas alpha-nya BELUM lewat (relatif
     * ke waktu SEKARANG) otomatis dilewati, tidak akan salah ditandai
     * alpha sebelum waktunya.
     *
     * @return array{marked:int,skipped_existing:int,skipped_leave:int,skipped_exempt:int,skipped_too_early:int}
     */
    public function runAlphaMarking(string $targetDate): array
    {
        $pdo = $this->db();
        $policyModel  = new RolePolicy();
        $holidayModel = new Holiday();
        $userShiftM   = new UserShift();

        $policies = [];
        foreach ($policyModel->all() as $p) $policies[(int)$p['role_id']] = $p;
        $holiday = $holidayModel->findBy('tanggal', $targetDate);

        $users = $pdo->query("SELECT id, role_id FROM users WHERE is_active = 1")->fetchAll();

        $stats = ['marked'=>0,'skipped_existing'=>0,'skipped_leave'=>0,'skipped_exempt'=>0,'skipped_too_early'=>0];

        $stmtExisting = $pdo->prepare("SELECT id FROM attendances WHERE user_id = ? AND tanggal = ? LIMIT 1");
        $stmtLeave    = $pdo->prepare(
            "SELECT id FROM leave_requests
             WHERE user_id = ? AND status = 'approved'
               AND ? BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1"
        );

        foreach ($users as $u) {
            $userId = (int)$u['id'];
            $policy = $policies[(int)$u['role_id']] ?? null;

            $stmtExisting->execute([$userId, $targetDate]);
            if ($stmtExisting->fetch()) { $stats['skipped_existing']++; continue; }

            $stmtLeave->execute([$userId, $targetDate]);
            if ($stmtLeave->fetch()) { $stats['skipped_leave']++; continue; }

            $assignment = $userShiftM->defaultAssignment($userId);

            if (is_exempt_from_alpha($policy, $assignment, $targetDate, $holiday)) { $stats['skipped_exempt']++; continue; }

            $cutoff = alpha_cutoff_datetime($policy, $assignment, $targetDate);
            if (time() < strtotime($cutoff)) { $stats['skipped_too_early']++; continue; }

            try {
                $this->create([
                    'user_id'    => $userId,
                    'shift_id'   => $assignment['shift_id'] ?? null,
                    'tanggal'    => $targetDate,
                    'jam_masuk'  => null,
                    'status'     => 'alpha',
                    'keterangan' => 'Ditandai otomatis oleh sistem — tidak ada absen masuk s/d batas ' . substr($cutoff, 11, 5),
                ]);
                $stats['marked']++;
            } catch (\Throwable $e) {
                // UNIQUE KEY (user_id, tanggal) -- kondisi balapan, aman diabaikan.
            }
        }

        return $stats;
    }

    public function todayFor(int $userId): ?array
    {
        // Cek dulu apakah ada sesi absen yang masih terbuka (sudah absen masuk,
        // belum absen pulang). Ini wajib untuk shift yang melewati tengah malam
        // (mis. Shift Satpam Malam 23:00-07:00): baris absennya tersimpan dengan
        // `tanggal` = tanggal saat absen MASUK (hari sebelumnya), sehingga jika kita
        // hanya mencari berdasarkan tanggal HARI INI, baris tsb tidak ketemu dan
        // karyawan dikira belum absen masuk saat mau absen pulang.
        $stmt = $this->db()->prepare(
            "SELECT * FROM attendances
             WHERE user_id = ? AND jam_masuk IS NOT NULL AND jam_keluar IS NULL
               AND jam_masuk >= (NOW() - INTERVAL 20 HOUR)
             ORDER BY jam_masuk DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        $open = $stmt->fetch();
        if ($open) return $open;

        // Tidak ada sesi terbuka -> cek baris untuk tanggal hari ini (untuk kasus
        // absen masuk baru, atau yang sudah selesai masuk+pulang hari ini).
        $today = date('Y-m-d');
        $stmt = $this->db()->prepare("SELECT * FROM attendances WHERE user_id = ? AND tanggal = ? LIMIT 1");
        $stmt->execute([$userId, $today]);
        return $stmt->fetch() ?: null;
    }

    public function statsToday(): array
    {
        $today = date('Y-m-d');
        $stmt = $this->db()->prepare(
            "SELECT status, COUNT(*) AS total
             FROM attendances WHERE tanggal = ? GROUP BY status"
        );
        $stmt->execute([$today]);
        $rows = $stmt->fetchAll();
        $out = ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
        foreach ($rows as $r) $out[$r['status']] = (int)$r['total'];
        return $out;
    }

    public function history(int $userId, int $month, int $year): array
    {
                $stmt = $this->db()->prepare(
                        "SELECT a.*, s.nama AS shift_nama, s.jam_masuk AS shift_jam_masuk, s.toleransi_menit,
                                        IF(a.jam_masuk IS NULL, NULL,
                                            GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(a.tanggal, ' ', s.jam_masuk), a.jam_masuk) - s.toleransi_menit)
                                        ) AS terlambat_menit
                         FROM attendances a
                         LEFT JOIN shifts s ON s.id = a.shift_id
                         WHERE a.user_id = ? AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
                         ORDER BY a.tanggal DESC"
                );
                $stmt->execute([$userId, $month, $year]);
                return $stmt->fetchAll();
    }

    public function historyRange(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db()->prepare(
            "SELECT a.*, s.nama AS shift_nama, s.jam_masuk AS shift_jam_masuk, s.toleransi_menit,
                    IF(a.jam_masuk IS NULL, NULL,
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(a.tanggal, ' ', s.jam_masuk), a.jam_masuk) - s.toleransi_menit)
                    ) AS terlambat_menit
             FROM attendances a
             LEFT JOIN shifts s ON s.id = a.shift_id
             WHERE a.user_id = ? AND a.tanggal BETWEEN ? AND ?
             ORDER BY a.tanggal DESC"
        );
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

        /** Daily report for HRD view — include all active users, with 'belum_absen' status for missing records */
        public function dailyReport(string $date): array
        {
                $stmt = $this->db()->prepare(
                        "SELECT
                            a.id AS attendance_id,
                            COALESCE(a.user_id, u.id) AS user_id,
                            u.id AS user_id_ref,
                            u.niy,
                            u.nama,
                            r.name AS role_name,
                            a.shift_id,
                            a.tanggal,
                            a.jam_masuk,
                            a.jam_keluar,
                            a.foto_masuk,
                            a.foto_keluar,
                            a.lat_masuk,
                            a.lng_masuk,
                            a.lat_keluar,
                            a.lng_keluar,
                            a.face_match_masuk,
                            a.face_match_keluar,
                            CASE WHEN a.id IS NULL THEN 'belum_absen' ELSE a.status END AS status,
                            a.keterangan,
                            a.created_at,
                            s.nama AS shift_nama,
                            s.jam_masuk AS shift_jam_masuk,
                            s.toleransi_menit,
                            IF(a.jam_masuk IS NULL, NULL,
                                GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(a.tanggal, ' ', s.jam_masuk), a.jam_masuk) - s.toleransi_menit)
                            ) AS terlambat_menit
                         FROM users u
                         LEFT JOIN roles r ON r.id = u.role_id
                         LEFT JOIN attendances a ON a.user_id = u.id AND a.tanggal = ?
                         LEFT JOIN shifts s ON s.id = a.shift_id
                         WHERE u.is_active = 1
                         ORDER BY CASE WHEN a.id IS NULL THEN 1 ELSE 0 END,
                                  COALESCE(a.jam_masuk, '9999-12-31 23:59:59') ASC,
                                  u.nama ASC"
                );
                $stmt->execute([$date]);
                return $stmt->fetchAll();
        }

    public function summaryMonth(int $userId, int $month, int $year): array
    {
        $stmt = $this->db()->prepare(
            "SELECT status, COUNT(*) AS total
             FROM attendances WHERE user_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
             GROUP BY status"
        );
        $stmt->execute([$userId, $month, $year]);
        $out = ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
        foreach ($stmt->fetchAll() as $r) $out[$r['status']] = (int)$r['total'];
        return $out;
    }

    public function summaryRange(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db()->prepare(
            "SELECT status, COUNT(*) AS total
             FROM attendances WHERE user_id = ? AND tanggal BETWEEN ? AND ?
             GROUP BY status"
        );
        $stmt->execute([$userId, $startDate, $endDate]);
        $out = ['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
        foreach ($stmt->fetchAll() as $r) $out[$r['status']] = (int)$r['total'];
        return $out;
    }

    /** Trend N hari kebelakang utk Chart.js (HRD dashboard) */
    public function last7DaysCounts(int $days = 7): array
    {
        $stmt = $this->db()->prepare(
            "SELECT tanggal, status, COUNT(*) AS total
             FROM attendances
             WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY tanggal, status
             ORDER BY tanggal ASC"
        );
        $stmt->execute([$days - 1]);
        $rows = $stmt->fetchAll();

        $labels = [];
        $hadir = []; $telat = []; $absen = [];
        $by = [];
        foreach ($rows as $r) {
            $by[$r['tanggal']][$r['status']] = (int)$r['total'];
        }
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $labels[] = date('d/m', strtotime($d));
            $hadir[] = (int)($by[$d]['hadir'] ?? 0);
            $telat[] = (int)($by[$d]['telat'] ?? 0);
            $absen[] = (int)(($by[$d]['izin']??0) + ($by[$d]['sakit']??0) + ($by[$d]['alpha']??0));
        }
        return ['labels'=>$labels, 'hadir'=>$hadir, 'telat'=>$telat, 'absen'=>$absen];
    }

    /** Streak hadir berturut-turut (mundur dari hari ini) */
    public function streakFor(int $userId): int
    {
        $stmt = $this->db()->prepare(
            "SELECT tanggal, status FROM attendances
             WHERE user_id = ? AND status IN ('hadir','telat')
             ORDER BY tanggal DESC LIMIT 60"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $streak = 0;
        $cur = strtotime(date('Y-m-d'));
        foreach ($rows as $r) {
            $d = strtotime($r['tanggal']);
            // skip akhir pekan
            while (in_array((int)date('w', $cur), [0,6], true)) $cur -= 86400;
            if ($d === $cur) { $streak++; $cur -= 86400; }
            elseif ($d < $cur) break;
        }
        return $streak;
    }

    /** Total jam kerja minggu ini (jam) */
    public function workHoursThisWeek(int $userId): float
    {
        $stmt = $this->db()->prepare(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, jam_masuk, jam_keluar))/60 AS jam
             FROM attendances
             WHERE user_id = ?
               AND jam_keluar IS NOT NULL
               AND YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)"
        );
        $stmt->execute([$userId]);
        return round((float)$stmt->fetchColumn(), 1);
    }

    /** Rekap HRD: per user untuk periode (bulan,tahun) */
    public function rekapPeriode(int $month, int $year): array
    {
        $stmt = $this->db()->prepare(
            "SELECT u.id, u.niy, u.nama, r.name AS role_name,
                    SUM(a.status='hadir') AS hadir,
                    SUM(a.status='telat') AS telat,
                    SUM(a.status='izin')  AS izin,
                    SUM(a.status='sakit') AS sakit,
                    SUM(a.status='alpha') AS alpha,
                    COUNT(a.id) AS total
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN attendances a
                ON a.user_id = u.id
               AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
             WHERE u.is_active = 1
             GROUP BY u.id
             ORDER BY r.name, u.nama"
        );
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }

    public function rekapRange(string $startDate, string $endDate): array
    {
        $stmt = $this->db()->prepare(
            "SELECT u.id, u.niy, u.nama, r.name AS role_name,
                    SUM(a.status='hadir') AS hadir,
                    SUM(a.status='telat') AS telat,
                    SUM(a.status IN ('hadir','telat')) AS total_hadir,
                    SUM(a.status='izin')  AS izin,
                    SUM(a.status='sakit') AS sakit,
                    SUM(a.status='alpha') AS alpha,
                    SUM(
                        IF(a.status='telat' AND a.jam_masuk IS NOT NULL AND s.jam_masuk IS NOT NULL,
                            GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(a.tanggal, ' ', s.jam_masuk), a.jam_masuk) - s.toleransi_menit),
                            0
                        )
                    ) AS menit_telat,
                    COUNT(a.id) AS total
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN attendances a
                ON a.user_id = u.id
               AND a.tanggal BETWEEN ? AND ?
             LEFT JOIN shifts s ON s.id = a.shift_id
             WHERE u.is_active = 1
             GROUP BY u.id
             ORDER BY r.name, u.nama"
        );
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
}
