<?php

namespace App\Models;

use App\Core\Model;

/**
 * Riwayat pemotongan jatah cuti oleh HRD (tabel leave_deductions).
 * Pemotongan mengurangi users.jumlah_cuti dan sekaligus menjadi sumber
 * notifikasi (type 'leave_deduction') untuk karyawan yang dipotong.
 */
class LeaveDeduction extends Model
{
    protected string $table = 'leave_deductions';

    /**
     * Potong jatah cuti $userId sebanyak $days hari.
     *
     * Dijalankan dalam satu transaksi dgn row-lock pada baris user, supaya
     * dua HRD yg memotong bersamaan (atau double-submit) tidak menghasilkan
     * sisa cuti yg salah / negatif.
     *
     * @return array ['ok'=>true, 'id'=>int, 'sisa_sebelum'=>int, 'sisa_sesudah'=>int]
     *               atau ['ok'=>false, 'error'=>string]
     */
    public function deduct(int $userId, int $days, string $reason, int $hrId): array
    {
        $db = $this->db();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT jumlah_cuti FROM users WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Karyawan tidak ditemukan.'];
            }

            $before = (int)$row['jumlah_cuti'];
            if ($days < 1) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Jumlah hari minimal 1.'];
            }
            if ($days > $before) {
                $db->rollBack();
                return ['ok' => false, 'error' => "Jumlah potongan ({$days} hari) melebihi sisa cuti karyawan ({$before} hari)."];
            }

            $after = $before - $days;
            $db->prepare('UPDATE users SET jumlah_cuti = ? WHERE id = ? LIMIT 1')
               ->execute([$after, $userId]);

            $db->prepare(
                'INSERT INTO leave_deductions
                    (user_id, deducted_by, jumlah_hari, sisa_sebelum, sisa_sesudah, alasan)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$userId, $hrId, $days, $before, $after, $reason]);
            $id = (int)$db->lastInsertId();

            $db->commit();
            return ['ok' => true, 'id' => $id, 'sisa_sebelum' => $before, 'sisa_sesudah' => $after];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[LeaveDeduction::deduct] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal menyimpan pemotongan cuti. Pastikan migrasi database (migration_potong_cuti.sql) sudah dijalankan.'];
        }
    }

    /** Riwayat pemotongan seorang karyawan (terbaru dulu), lengkap dgn nama HRD. */
    public function listForUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db()->prepare(
            "SELECT ld.*, h.nama AS hr_nama
             FROM leave_deductions ld
             LEFT JOIN users h ON h.id = ld.deducted_by
             WHERE ld.user_id = ?
             ORDER BY ld.created_at DESC, ld.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Satu pemotongan + nama HRD — dipakai halaman detail notifikasi. */
    public function findWithHr(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT ld.*, h.nama AS hr_nama
             FROM leave_deductions ld
             LEFT JOIN users h ON h.id = ld.deducted_by
             WHERE ld.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
