<?php
/**
 * Helper upload: simpan base64 image (selfie) ke /public/uploads/<dir>/.
 */

if (!function_exists('save_base64_image')) {
    /**
     * @param string $dataUri  "data:image/jpeg;base64,..."
     * @param string $dir      relative dir di public/uploads, e.g. "attendance"
     * @param int    $maxBytes maksimum ukuran file (decoded)
     * @return string|null path relatif (e.g. "attendance/abc.jpg") atau null kalau invalid
     */
    function save_base64_image(string $dataUri, string $dir, int $maxBytes = 3145728): ?string
    {
        if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#i', $dataUri, $m)) {
            return null;
        }
        $ext  = strtolower($m[1]) === 'jpg' ? 'jpeg' : strtolower($m[1]);
        $bin  = base64_decode($m[2], true);
        if ($bin === false || $bin === '') return null;
        if (strlen($bin) > $maxBytes) return null;

        // Validasi mime via finfo (anti file palsu)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($bin);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) return null;

        $targetDir = UPLOADS_PATH . '/' . trim($dir, '/');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return null;
        }

        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $full = $targetDir . '/' . $name;
        if (file_put_contents($full, $bin) === false) return null;

        return trim($dir, '/') . '/' . $name;
    }
}

if (!function_exists('haversine_meters')) {
    /**
     * Jarak antara 2 koordinat dalam meter (Haversine).
     */
    function haversine_meters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0; // bumi (m)
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return 2 * $R * asin(sqrt($a));
    }
}
