<?php

namespace App\Models;

use App\Core\Model;

class LeaveRequest extends Model
{
    protected string $table = 'leave_requests';

    public static function isDateWithinLeavePeriod(string $date, array $leave): bool
    {
        $start = strtotime($leave['tanggal_mulai'] ?? '');
        $end   = strtotime($leave['tanggal_selesai'] ?? '');
        $target = strtotime($date);

        if ($start === false || $end === false || $target === false || $start > $end) {
            return false;
        }

        return $target >= $start && $target <= $end;
    }

    public function approvedForDate(int $userId, string $date): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT lr.* FROM {$this->table} lr
             JOIN leave_request_dates lrd ON lrd.leave_request_id = lr.id
             WHERE lr.user_id = ? AND lr.status = 'approved' AND lrd.tanggal = ?
             ORDER BY lr.id DESC LIMIT 1"
        );
        $stmt->execute([$userId, $date]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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
                       v.nama AS verifier_nama,
                       GROUP_CONCAT(lrd.tanggal ORDER BY lrd.tanggal SEPARATOR ',') AS tanggal_list
                FROM leave_requests lr
                JOIN users u  ON u.id = lr.user_id
                JOIN roles r  ON r.id = u.role_id
                LEFT JOIN users v ON v.id = lr.verified_by
                LEFT JOIN leave_request_dates lrd ON lrd.leave_request_id = lr.id
                WHERE r.name IN ($placeholders)";
        $par = $roleNames;
        if ($status) { $sql .= " AND lr.status = ?"; $par[] = $status; }
        $sql .= " GROUP BY lr.id
                  ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC";
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
            "SELECT lr.*, v.nama AS verifier_nama,
                    GROUP_CONCAT(lrd.tanggal ORDER BY lrd.tanggal SEPARATOR ',') AS tanggal_list
             FROM leave_requests lr
             LEFT JOIN users v ON v.id = lr.verified_by
             LEFT JOIN leave_request_dates lrd ON lrd.leave_request_id = lr.id
             WHERE lr.user_id = ?
             GROUP BY lr.id
             ORDER BY lr.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Tanggal yang SUDAH terpakai cuti (status pending/approved) utk satu
     * karyawan. Dipakai utk memblokir tanggal yg sama dipilih dobel saat
     * mengajukan/mengubah cuti lain.
     * Return: ['YYYY-MM-DD' => ['jenis'=>.., 'status'=>.., 'note'=>.., 'leave_id'=>..]]
     */
    public function existingDatesForUser(int $userId, ?int $excludeId = null): array
    {
        $sql = "SELECT lrd.tanggal, lrd.keterangan AS date_note, lr.id AS leave_id, lr.jenis, lr.status, lr.alasan
                FROM leave_request_dates lrd
                JOIN {$this->table} lr ON lr.id = lrd.leave_request_id
                WHERE lr.user_id = ? AND lr.status IN ('pending','approved')";
        $params = [$userId];
        if ($excludeId !== null) {
            $sql .= " AND lr.id <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['tanggal']] = [
                'leave_id' => (int)$row['leave_id'],
                'jenis'    => $row['jenis'],
                'status'   => $row['status'],
                'note'     => $row['date_note'] ?: $row['alasan'],
            ];
        }
        return $map;
    }

    /**
     * Daftar tanggal (+ keterangan per-tanggal, kalau ada) utk satu
     * pengajuan cuti, urut tanggal.
     * Return: [['tanggal'=>'YYYY-MM-DD', 'keterangan'=>string|null], ...]
     */
    public function datesFor(int $leaveId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT tanggal, keterangan FROM leave_request_dates
             WHERE leave_request_id = ? ORDER BY tanggal ASC"
        );
        $stmt->execute([$leaveId]);
        return $stmt->fetchAll();
    }

    /**
     * Ganti seluruh tanggal utk satu pengajuan cuti (hapus yg lama, tulis
     * yg baru) — dipakai saat membuat maupun mengubah pengajuan, supaya
     * SATU pengajuan selalu jadi SATU baris leave_requests, walau tanggal
     * yg dicakup tidak berurutan.
     * $dates: ['YYYY-MM-DD' => keterangan|null, ...]
     */
    public function replaceDates(int $leaveId, array $dates): void
    {
        $del = $this->db()->prepare("DELETE FROM leave_request_dates WHERE leave_request_id = ?");
        $del->execute([$leaveId]);

        if (empty($dates)) return;
        $ins = $this->db()->prepare(
            "INSERT INTO leave_request_dates (leave_request_id, tanggal, keterangan) VALUES (?, ?, ?)"
        );
        foreach ($dates as $tanggal => $ket) {
            $ins->execute([$leaveId, $tanggal, ($ket !== null && $ket !== '') ? $ket : null]);
        }
    }

    /** Buat pengajuan baru (SATU baris) sekaligus tanggal-tanggalnya. */
    public function createWithDates(array $parentData, array $dates): int
    {
        $sorted = array_keys($dates);
        sort($sorted);
        $parentData['tanggal_mulai']   = $sorted[0];
        $parentData['tanggal_selesai'] = end($sorted);

        $id = $this->create($parentData);
        $this->replaceDates($id, $dates);
        return $id;
    }

    /** Perbarui pengajuan yg sudah ada (field umum) + timpa tanggal2nya. */
    public function updateWithDates(int $id, array $parentData, array $dates): void
    {
        $sorted = array_keys($dates);
        sort($sorted);
        $parentData['tanggal_mulai']   = $sorted[0];
        $parentData['tanggal_selesai'] = end($sorted);

        $this->update($id, $parentData);
        $this->replaceDates($id, $dates);
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
