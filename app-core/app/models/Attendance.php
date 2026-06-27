<?php

namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{
    protected string $table = 'attendances';

    public function todayFor(int $userId): ?array
    {
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
}
