<?php

namespace App\Models;

use App\Core\Model;

/**
 * Tracking notifikasi yang sudah dibaca per user.
 * type: 'announcement' | 'leave_status'
 * ref_id: id record terkait (announcements.id atau leave_requests.id)
 */
class NotificationRead extends Model
{
    protected string $table = 'notification_reads';

    /** Daftar id yang sudah dibaca user untuk type tertentu */
    public function readIds(int $userId, string $type): array
    {
        $stmt = $this->db()->prepare(
            "SELECT ref_id FROM notification_reads WHERE user_id = ? AND type = ?"
        );
        $stmt->execute([$userId, $type]);
        return array_map('intval', array_column($stmt->fetchAll(), 'ref_id'));
    }

    public function markRead(int $userId, string $type, int $refId): void
    {
        $stmt = $this->db()->prepare(
            "INSERT IGNORE INTO notification_reads (user_id, type, ref_id, read_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $type, $refId]);
    }

    public function markAll(int $userId, array $items): void
    {
        if (!$items) return;
        $stmt = $this->db()->prepare(
            "INSERT IGNORE INTO notification_reads (user_id, type, ref_id, read_at)
             VALUES (?, ?, ?, NOW())"
        );
        foreach ($items as [$type, $refId]) {
            $stmt->execute([$userId, $type, (int)$refId]);
        }
    }

    /**
     * Bangun feed notifikasi gabungan untuk user (mobile pegawai/kepsek).
     * Menggabungkan: announcements (published) + perubahan status cuti milik user.
     * Return: list of [type, ref_id, judul, isi, icon, url, created_at, is_read]
     */
    public function feedFor(int $userId): array
    {
        $db = $this->db();

        // Pengumuman published
        $ann = $db->query(
            "SELECT id, judul, isi, image, created_at
             FROM announcements WHERE is_published = 1
             ORDER BY created_at DESC LIMIT 30"
        )->fetchAll();

        // Cuti milik user yang sudah diverifikasi (approved/rejected)
        $stmt = $db->prepare(
            "SELECT lr.id, lr.jenis, lr.status, lr.catatan, lr.tanggal_mulai, lr.tanggal_selesai,
                    lr.updated_at AS created_at, v.nama AS verifier_nama
             FROM leave_requests lr
             LEFT JOIN users v ON v.id = lr.verified_by
             WHERE lr.user_id = ? AND lr.status IN ('approved','rejected')
             ORDER BY lr.updated_at DESC LIMIT 30"
        );
        $stmt->execute([$userId]);
        $cuti = $stmt->fetchAll();

        $readAnn  = $this->readIds($userId, 'announcement');
        $readCuti = $this->readIds($userId, 'leave_status');

        $feed = [];
        foreach ($ann as $a) {
            $feed[] = [
                'type'       => 'announcement',
                'ref_id'     => (int)$a['id'],
                'judul'      => $a['judul'],
                'isi'        => $a['isi'],
                'image'      => $a['image'] ?? null,
                'icon'       => 'bi-megaphone-fill',
                'tone'       => 'primary',
                'url'        => url('/notifikasi/announcement/' . $a['id']),
                'created_at' => $a['created_at'],
                'is_read'    => in_array((int)$a['id'], $readAnn, true),
            ];
        }
        foreach ($cuti as $c) {
            $approved = $c['status'] === 'approved';
            $jenis = ucfirst((string)$c['jenis']);
            $period = format_date_id($c['tanggal_mulai']) .
                      ($c['tanggal_mulai'] !== $c['tanggal_selesai']
                          ? ' — ' . format_date_id($c['tanggal_selesai']) : '');
            $catatan = trim((string)($c['catatan'] ?? ''));
            $isi = "Pengajuan {$jenis} ({$period}) " .
                   ($approved ? 'DISETUJUI' : 'DITOLAK') .
                   ($c['verifier_nama'] ? ' oleh ' . $c['verifier_nama'] : '') .
                   ($catatan !== '' ? ". Catatan: {$catatan}" : '.');
            $feed[] = [
                'type'       => 'leave_status',
                'ref_id'     => (int)$c['id'],
                'judul'      => 'Pengajuan Cuti ' . ($approved ? 'Disetujui' : 'Ditolak'),
                'isi'        => $isi,
                'icon'       => $approved ? 'bi-check-circle-fill' : 'bi-x-circle-fill',
                'tone'       => $approved ? 'success' : 'danger',
                'url'        => url('/notifikasi/leave_status/' . $c['id']),
                'created_at' => $c['created_at'],
                'is_read'    => in_array((int)$c['id'], $readCuti, true),
            ];
        }

        usort($feed, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $feed;
    }

    public function unreadCountFor(int $userId): int
    {
        $feed = $this->feedFor($userId);
        return count(array_filter($feed, fn($n) => !$n['is_read']));
    }
}
