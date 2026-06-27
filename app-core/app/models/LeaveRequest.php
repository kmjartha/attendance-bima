<?php

namespace App\Models;

use App\Core\Model;

class LeaveRequest extends Model
{
    protected string $table = 'leave_requests';

    public function pendingCount(): int
    {
        return $this->count("status = 'pending'");
    }

    public function pendingCountForRoles(array $roleNames): int
    {
        if (empty($roleNames)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($roleNames), '?'));
        $sql = "SELECT COUNT(*) FROM {$this->table} lr
                JOIN users u ON u.id = lr.user_id
                JOIN roles r ON r.id = u.role_id
                WHERE lr.status = ? AND r.name IN ({$placeholders})";
        $params = array_merge(['pending'], $roleNames);
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function listAll(?string $status = null): array
    {
        $sql = "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                       v.nama AS verifier_nama
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by";
        $par = [];
        if ($status) { $sql .= " WHERE lr.status = ?"; $par[] = $status; }
        $sql .= " ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll();
    }

    public function listForRole(string $roleName, ?string $status = null): array
    {
        $sql = "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                       v.nama AS verifier_nama
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by
                WHERE r.name = ?";
        $par = [$roleName];
        if ($status) { $sql .= " AND lr.status = ?"; $par[] = $status; }
        $sql .= " ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll();
    }

    public function listForRoles(array $roleNames, ?string $status = null): array
    {
        if (empty($roleNames)) return [];
        $placeholders = implode(',', array_fill(0, count($roleNames), '?'));
        $sql = "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                       v.nama AS verifier_nama
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by
                WHERE r.name IN ($placeholders)";
        $par = $roleNames;
        if ($status) { $sql .= " AND lr.status = ?"; $par[] = $status; }
        $sql .= " ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll();
    }

    public function listForGuruOnly(?string $status = null): array
    {
        $sql = "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                       v.nama AS verifier_nama
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by
                WHERE r.name = 'Guru'";
        $par = [];
        if ($status) { $sql .= " AND lr.status = ?"; $par[] = $status; }
        $sql .= " ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll();
    }

    public function listNonHrd(?string $status = null): array
    {
        $sql = "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                       v.nama AS verifier_nama
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by
                WHERE r.name <> 'HRD'";
        $par = [];
        if ($status) { $sql .= " AND lr.status = ?"; $par[] = $status; }
        $sql .= " ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return $stmt->fetchAll();
    }

    public function listFor(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT lr.*, v.nama AS verifier_nama
             FROM leave_requests lr
             LEFT JOIN users v ON v.id = lr.verified_by
             WHERE lr.user_id = ?
             ORDER BY lr.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findWithUser(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT lr.*, u.nama AS user_nama, u.niy AS user_niy, r.name AS user_role,
                    u.jumlah_cuti AS user_jumlah_cuti
             FROM leave_requests lr
             JOIN users u ON u.id = lr.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE lr.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
