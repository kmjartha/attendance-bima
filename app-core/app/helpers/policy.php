<?php
/**
 * Helper kebijakan absensi per role & per shift assignment.
 *
 * Latar belakang: sebelum ini, status "alpha" ada di skema database dan
 * di semua query laporan (Attendance::rekapPeriode dkk), tapi TIDAK ADA
 * proses apapun yang benar-benar menuliskan baris berstatus alpha —
 * baris attendances hanya pernah dibuat saat user absen masuk. Akibatnya
 * kolom alpha di laporan selalu 0 walau kenyataannya ada karyawan yang
 * tidak pernah absen. app/cron/mark_alpha.php mengisi kekosongan itu,
 * dan fungsi-fungsi di file ini adalah aturan yang dipakai bersama oleh
 * cron tsb dan oleh AbsensiController (utk validasi realtime).
 *
 * SUMBER KEBENARAN "hari efektif kerja": kolom `user_shifts.hari_aktif`
 * (mis. 'Senin,Selasa,Rabu,Kamis,Jumat') sudah ada di skema dan dipakai
 * utk assignment shift per karyawan — itulah yang dipakai di sini sebagai
 * sumber utama, BUKAN flag exempt_weekend di role_policies. Flag role
 * hanya jadi fallback utk role yang tidak punya assignment shift sama
 * sekali (HRD, Kepsek, Supervisor, Manajerial — staf kantor yang
 * di data nyata memang tidak diberi baris user_shifts).
 */

if (!function_exists('indo_day_name')) {
    /** Nama hari dalam Bahasa Indonesia, format sama persis dgn isi kolom hari_aktif. */
    function indo_day_name(string $date): string
    {
        $map = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        return $map[date('l', strtotime($date))] ?? '';
    }
}

if (!function_exists('is_weekend_date')) {
    function is_weekend_date(string $date): bool
    {
        $w = (int)date('w', strtotime($date)); // 0 = Minggu, 6 = Sabtu
        return $w === 0 || $w === 6;
    }
}

if (!function_exists('is_scheduled_workday')) {
    /**
     * True jika $date adalah hari kerja efektif untuk assignment shift ybs,
     * berdasarkan kolom hari_aktif ('Senin,Selasa,...'). $assignment adalah
     * hasil UserShift::defaultAssignment() — null kalau user tidak punya
     * shift sama sekali (kembalikan null, bukan bool, supaya caller tahu
     * harus fallback ke kebijakan role).
     */
    function is_scheduled_workday(?array $assignment, string $date): ?bool
    {
        if (!$assignment || empty($assignment['hari_aktif'])) return null;
        $days = array_map('trim', explode(',', $assignment['hari_aktif']));
        return in_array(indo_day_name($date), $days, true);
    }
}

if (!function_exists('is_exempt_from_alpha')) {
    /**
     * True jika user DIKECUALIKAN dari status alpha pada tanggal $date.
     *
     * Urutan pengecekan:
     *  1. Jika $date ada di tabel holidays -> otomatis exempt (libur nasional/
     *     hari libur berlaku untuk semua tanpa terkecuali)
     *  2. Kalau user punya shift assignment (hari_aktif terisi): dikecualikan
     *     kalau $date BUKAN salah satu hari_aktif shift tsb (mis. Guru dgn
     *     hari_aktif Senin-Jumat otomatis dikecualikan di Sabtu/Minggu).
     *  3. Kalau user TIDAK punya shift sama sekali: fallback ke
     *     role_policies.exempt_weekend (utk HRD/Kepsek/Supervisor/dst).
     */
    function is_exempt_from_alpha(?array $policy, ?array $assignment, string $date, ?array $holiday): bool
    {
        // Priority 1: Jika ada holiday record, selalu exempt
        if ($holiday) return true;

        $scheduled = is_scheduled_workday($assignment, $date);

        if ($scheduled !== null) {
            // Ada hari_aktif eksplisit dari shift assignment -> itu yang menentukan.
            if ($scheduled === false) return true;
        } elseif ($policy && !empty($policy['exempt_weekend']) && is_weekend_date($date)) {
            // Tidak ada shift assignment -> fallback ke flag role.
            return true;
        }

        return false;
    }
}

if (!function_exists('alpha_cutoff_datetime')) {
    /**
     * Datetime batas alpha untuk user pada tanggal $date ('Y-m-d H:i:s').
     * Prioritas:
     *   1. Jam masuk shift assignment user + toleransi_menit (paling akurat,
     *      spesifik per karyawan — dipakai Guru, Staff, Satpam yang di data
     *      nyata memang selalu punya baris user_shifts)
     *   2. role_policies.alpha_cutoff_time (fallback utk role tanpa shift
     *      sama sekali, mis. HRD/Kepsek/Supervisor/Manajerial, atau
     *      karyawan baru yang belum sempat di-assign shift)
     *   3. Fallback 23:59 hari itu, kalau tidak ada info sama sekali
     */
    function alpha_cutoff_datetime(?array $policy, ?array $assignment, string $date): string
    {
        if ($assignment && !empty($assignment['jam_masuk'])) {
            $toleransi = (int)($assignment['toleransi_menit'] ?? 0);
            $ts = strtotime($date . ' ' . $assignment['jam_masuk']) + $toleransi * 60;
            return date('Y-m-d H:i:s', $ts);
        }
        if ($policy && !empty($policy['alpha_cutoff_time'])) {
            return $date . ' ' . $policy['alpha_cutoff_time'];
        }
        return $date . ' 23:59:00';
    }
}

if (!function_exists('is_checkin_blocked_now')) {
    /**
     * True jika absen MASUK sedang ditutup SEKARANG berdasarkan
     * checkin_block_time role ybs. Dipakai realtime di
     * AbsensiController::submit() — hanya utk type=masuk, tidak pernah
     * memblokir absen pulang.
     *
     * Window yang diblokir: dari jam checkin_block_time sampai jam 03:59
     * dini hari berikutnya (dianggap di luar jam kerja wajar). Role dgn
     * allow_overnight=1 (mis. Satpam shift malam) tidak pernah kena
     * blokir ini sama sekali, berapapun jamnya.
     */
    function is_checkin_blocked_now(?array $policy): bool
    {
        if (!$policy) return false;
        if (!empty($policy['allow_overnight'])) return false;
        if (empty($policy['checkin_block_time'])) return false;

        $blockTime = substr((string)$policy['checkin_block_time'], 0, 8);
        $nowTime   = date('H:i:s');
        return $nowTime >= $blockTime || $nowTime < '04:00:00';
    }
}
