<?php

namespace App\Models;

use App\Core\Model;

class UserShift extends Model
{
    protected string $table = 'user_shifts';

    /**
     * Replace all shifts assigned to a user with the given list.
     * The first id in the array is marked as default.
     */
    public function setShifts(int $userId, array $shiftIds): void
    {
        $shiftIds = array_values(array_unique(array_map('intval', array_filter($shiftIds))));

        $db = $this->db();
        $db->prepare("DELETE FROM user_shifts WHERE user_id = ?")->execute([$userId]);

        if (empty($shiftIds)) return;

        $stmt = $db->prepare("INSERT INTO user_shifts (user_id, shift_id, is_default) VALUES (?, ?, ?)");
        foreach ($shiftIds as $i => $sid) {
            $stmt->execute([$userId, $sid, $i === 0 ? 1 : 0]);
        }
    }

    /** Assign a single shift to multiple users without removing their other assignments. */
    public function assignShiftToUsers(int $shiftId, array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));
        if (empty($userIds)) return;

        $db = $this->db();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $db->prepare("SELECT user_id, shift_id FROM user_shifts WHERE user_id IN ($placeholders)");
        $stmt->execute($userIds);

        $existing = [];
        $hasExisting = [];
        foreach ($stmt->fetchAll() as $row) {
            $existing[(int)$row['user_id']][] = (int)$row['shift_id'];
            $hasExisting[(int)$row['user_id']] = true;
        }

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM user_shifts WHERE user_id = ?");
        $stmtInsert = $db->prepare("INSERT INTO user_shifts (user_id, shift_id, is_default) VALUES (?, ?, ?)");

        foreach ($userIds as $userId) {
            if (isset($existing[$userId]) && in_array($shiftId, $existing[$userId], true)) {
                continue;
            }

            $stmtCount->execute([$userId]);
            $isDefault = ((int)$stmtCount->fetchColumn() === 0) ? 1 : 0;
            $stmtInsert->execute([$userId, $shiftId, $isDefault]);
        }
    }

    /** Backwards-compatible single shift assignment. */
    public function setDefault(int $userId, int $shiftId): void
    {
        $this->setShifts($userId, [$shiftId]);
    }

    public function defaultShiftId(int $userId): ?int
    {
        $stmt = $this->db()->prepare("SELECT shift_id FROM user_shifts WHERE user_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $r = $stmt->fetchColumn();
        return $r ? (int)$r : null;
    }

    /** Return all shift ids assigned to user (default first). */
    public function shiftIdsFor(int $userId): array
    {
        $stmt = $this->db()->prepare("SELECT shift_id FROM user_shifts WHERE user_id = ? ORDER BY is_default DESC, id ASC");
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Return all shifts (joined) assigned to user. */
    public function shiftsFor(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT s.*, us.is_default
             FROM user_shifts us
             JOIN shifts s ON s.id = us.shift_id
             WHERE us.user_id = ?
             ORDER BY us.is_default DESC, s.jam_masuk ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function assignedUserIdsByShift(): array
    {
        $rows = $this->db()->query("SELECT shift_id, user_id FROM user_shifts ORDER BY shift_id ASC, user_id ASC")->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['shift_id']][] = (int)$row['user_id'];
        }
        return $result;
    }
}
