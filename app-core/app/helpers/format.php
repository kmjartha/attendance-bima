<?php

if (!function_exists('e')) {
    function e($val): string
    {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $type): ?string
    {
        return \App\Core\Session::flash($type);
    }
}

if (!function_exists('format_date_id')) {
    function format_date_id(?string $date, bool $withTime = false): string
    {
        if (!$date) return '-';
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime($date);
        $out = date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
        if ($withTime) $out .= ' ' . date('H:i', $ts);
        return $out;
    }
}

if (!function_exists('format_date_with_day_id')) {
    /**
     * Format date with day name in one string
     * @param string $date Date string (Y-m-d format)
     * @return string Formatted like "Sabtu, 4 Juli 2026"
     */
    function format_date_with_day_id(?string $date): string
    {
        if (!$date) return '-';
        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime($date);
        $dayName = $hari[(int)date('w', $ts)];
        $day = date('j', $ts);
        $monthName = $bulan[(int)date('n', $ts)];
        $year = date('Y', $ts);
        return "{$dayName}, {$day} {$monthName} {$year}";
    }
}

if (!function_exists('time_only')) {
    function time_only(?string $datetime): string
    {
        if (!$datetime) return '-';
        return date('H:i', strtotime($datetime));
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $first = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
        $last  = strtoupper(mb_substr($parts[count($parts)-1] ?? '', 0, 1));
        return $first . ($last !== $first ? $last : '');
    }
}

if (!function_exists('current_time')) {
    /**
     * Get current time in Asia/Makassar timezone
     * @param string $format Default 'Y-m-d H:i:s'
     * @return string Formatted current time
     */
    function current_time(string $format = 'Y-m-d H:i:s'): string
    {
        $timezone = date_default_timezone_get();
        try {
            $dt = new DateTime('now', new DateTimeZone($timezone));
        } catch (Exception $e) {
            $dt = new DateTime('now', new DateTimeZone('Asia/Makassar'));
        }
        return $dt->format($format);
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        if ($status === '-') return '—';
        
        $map = [
            'hadir'        => ['bg-success-subtle text-success', 'Hadir'],
            'telat'        => ['bg-warning-subtle text-warning', 'Telat'],
            'izin'         => ['bg-primary-subtle text-primary', 'Izin'],
            'sakit'        => ['bg-info-subtle text-info', 'Sakit'],
            'alpha'        => ['bg-danger-subtle text-danger', 'Alpha'],
            'belum_absen'  => ['bg-secondary-subtle text-secondary', 'Belum Absen'],
            'pending'      => ['bg-warning-subtle text-warning', 'Pending'],
            'approved'     => ['bg-success-subtle text-success', 'Disetujui'],
            'rejected'     => ['bg-danger-subtle text-danger', 'Ditolak'],
        ];
        [$cls, $label] = $map[strtolower($status)] ?? ['bg-secondary-subtle text-secondary', e(ucfirst($status))];
        return '<span class="badge rounded-pill ' . $cls . '">' . $label . '</span>';
    }
}

if (!function_exists('holiday_type_badge')) {
    function holiday_type_badge(string $tipe): string
    {
        $map = [
            'nasional'     => ['bg-primary-subtle text-primary', 'Nasional'],
            'cuti_bersama' => ['bg-info-subtle text-info', 'Cuti Bersama'],
            'sekolah'      => ['bg-secondary-subtle text-secondary', 'Sekolah'],
        ];
        [$cls, $label] = $map[$tipe] ?? ['bg-secondary-subtle text-secondary', e(ucfirst($tipe))];
        return '<span class="badge rounded-pill ' . $cls . '">' . $label . '</span>';
    }
}

if (!function_exists('day_name_id')) {
    /**
     * Get Indonesian day name from date string
     * @param string $date Date string (Y-m-d format)
     * @return string Indonesian day name (e.g., 'Senin', 'Selasa', etc.)
     */
    function day_name_id(string $date): string
    {
        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        return $hari[(int)date('w', strtotime($date))];
    }
}

if (!function_exists('format_leave_dates')) {
    /**
     * Format daftar tanggal cuti (dari GROUP_CONCAT leave_request_dates,
     * dipisah koma) jadi teks ringkas. Satu pengajuan sekarang bisa
     * mencakup tanggal yg tidak berurutan, jadi tidak selalu tampil
     * sebagai satu rentang "A — B".
     * @param string|null $csv  Tanggal dipisah koma, urut ASC (dari SQL)
     * @param string|null $fallbackStart tanggal_mulai (dipakai kalau $csv kosong, data lama blm di-backfill)
     * @param string|null $fallbackEnd   tanggal_selesai
     */
    function format_leave_dates(?string $csv, ?string $fallbackStart = null, ?string $fallbackEnd = null): string
    {
        $dates = $csv ? explode(',', $csv) : [];
        if (empty($dates) && $fallbackStart) {
            return format_date_id($fallbackStart) .
                   ($fallbackEnd && $fallbackEnd !== $fallbackStart ? ' — ' . format_date_id($fallbackEnd) : '');
        }
        if (empty($dates)) return '-';

        sort($dates);
        $n = count($dates);

        // Rentang berurutan (semua selisih 1 hari) -> tampil ringkas "A — B"
        $isContiguous = true;
        for ($i = 1; $i < $n; $i++) {
            if (strtotime($dates[$i]) - strtotime($dates[$i-1]) !== 86400) { $isContiguous = false; break; }
        }
        if ($isContiguous && $n > 1) {
            return format_date_id($dates[0]) . ' — ' . format_date_id(end($dates)) . " ({$n} hari)";
        }
        if ($n === 1) {
            return format_date_id($dates[0]);
        }
        // Tidak berurutan -> daftar tanggal (tanpa tahun berulang biar ringkas)
        $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $parts = array_map(fn($d) => (int)date('j', strtotime($d)) . ' ' . $bulan[(int)date('n', strtotime($d))], $dates);
        return implode(', ', $parts) . " ({$n} hari)";
    }
}
