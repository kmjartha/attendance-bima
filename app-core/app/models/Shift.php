<?php

namespace App\Models;

use App\Core\Model;

class Shift extends Model
{
    protected string $table = 'shifts';

    public function active(): array
    {
        return $this->db()->query("SELECT * FROM shifts WHERE is_active = 1 ORDER BY jam_masuk ASC")->fetchAll();
    }

    public function inUse(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT 1 FROM user_shifts WHERE shift_id = ? LIMIT 1");
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }
}
