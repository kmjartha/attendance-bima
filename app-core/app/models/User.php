<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByNiy(string $niy): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.niy = ? LIMIT 1"
        );
        $stmt->execute([$niy]);
        return $stmt->fetch() ?: null;
    }

    public function findWithRole(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function allWithRole(): array
    {
        return $this->db()->query(
            "SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY u.nama ASC"
        )->fetchAll();
    }

    public function niyExists(string $niy, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM users WHERE niy = ?";
        $par = [$niy];
        if ($exceptId) { $sql .= " AND id <> ?"; $par[] = $exceptId; }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return (bool)$stmt->fetch();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($email === '') return false;
        $sql = "SELECT id FROM users WHERE email = ?";
        $par = [$email];
        if ($exceptId) { $sql .= " AND id <> ?"; $par[] = $exceptId; }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($par);
        return (bool)$stmt->fetch();
    }
}
