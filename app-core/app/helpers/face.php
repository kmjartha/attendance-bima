<?php
/**
 * Helper face recognition (server-side verification).
 * Descriptor disimpan sebagai JSON array 128 float di kolom users.face_descriptor.
 */

if (!function_exists('face_decode')) {
    function face_decode(?string $json): ?array
    {
        if (!$json) return null;
        $arr = json_decode($json, true);
        if (!is_array($arr) || count($arr) < 64) return null;
        return array_map('floatval', $arr);
    }
}

if (!function_exists('face_distance')) {
    /**
     * Euclidean distance antara dua descriptor 128-dim.
     * Mengembalikan PHP_FLOAT_MAX bila input invalid.
     */
    function face_distance(?array $a, ?array $b): float
    {
        if (!$a || !$b) return PHP_FLOAT_MAX;
        $n = min(count($a), count($b));
        if ($n < 64) return PHP_FLOAT_MAX;
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
        }
        return sqrt($sum);
    }
}

if (!function_exists('face_match_score')) {
    /**
     * Konversi distance → skor kepercayaan 0–100.
     * distance 0   → 100, distance >= 1 → 0.
     */
    function face_match_score(float $distance): float
    {
        if ($distance >= 1.0) return 0.0;
        if ($distance <= 0.0) return 100.0;
        return round((1.0 - $distance) * 100, 2);
    }
}
