<?php

namespace App\Models;

use App\Core\Model;

class RolePolicy extends Model
{
    protected string $table = 'role_policies';
    protected string $primaryKey = 'role_id';

    public function forRole(int $roleId): ?array
    {
        return $this->find($roleId);
    }

    /** Semua kebijakan + nama role, urut nama role */
    public function allWithRole(): array
    {
        $stmt = $this->db()->query(
            "SELECT r.id AS role_id, r.name AS role_name, rp.alpha_cutoff_time,
                    rp.checkin_block_time, rp.exempt_weekend, rp.exempt_holiday,
                    rp.allow_overnight
             FROM roles r
             LEFT JOIN role_policies rp ON rp.role_id = r.id
             ORDER BY r.name"
        );
        return $stmt->fetchAll();
    }

    /** Upsert kebijakan utk 1 role (dipakai form Pengaturan) */
    public function upsert(int $roleId, array $data): void
    {
        $exists = $this->find($roleId);
        $data['role_id'] = $roleId;
        if ($exists) {
            unset($data['role_id']);
            $this->update($roleId, $data);
        } else {
            $this->create($data);
        }
    }
}
