<?php

namespace App\Models;

use App\Core\Model;

class Holiday extends Model
{
    protected string $table = 'holidays';

    public function isHoliday(string $date): bool
    {
        return $this->findBy('tanggal', $date) !== null;
    }

    /** Semua hari libur, urut tanggal. Bisa difilter per tahun. */
    public function allOrdered(?int $year = null): array
    {
        if ($year) {
            $stmt = $this->db()->prepare("SELECT * FROM holidays WHERE YEAR(tanggal) = ? ORDER BY tanggal ASC");
            $stmt->execute([$year]);
            return $stmt->fetchAll();
        }
        return $this->all('tanggal ASC');
    }

    /** Daftar tahun yang punya data libur (utk dropdown filter) */
    public function availableYears(): array
    {
        $stmt = $this->db()->query("SELECT DISTINCT YEAR(tanggal) AS y FROM holidays ORDER BY y DESC");
        return array_map('intval', array_column($stmt->fetchAll(), 'y'));
    }
}
