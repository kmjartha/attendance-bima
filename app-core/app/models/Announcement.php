<?php

namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected string $table = 'announcements';

    public function published(int $limit = 5): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM announcements WHERE is_published = 1 ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function allWithCreator(): array
    {
        return $this->db()->query(
            "SELECT a.*, u.nama AS creator_nama
             FROM announcements a
             LEFT JOIN users u ON u.id = a.created_by
             ORDER BY a.created_at DESC"
        )->fetchAll();
    }

    public function togglePublish(int $id): void
    {
        $this->db()->prepare("UPDATE announcements SET is_published = 1 - is_published WHERE id = ?")->execute([$id]);
    }
}
