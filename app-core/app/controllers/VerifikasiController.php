<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Attendance;

class VerifikasiController extends Controller
{
    /** GET /verifikasi-cuti */
    public function index(): string
    {
        if (!has_role('HRD','Supervisor','Kepsek')) return $this->forbid();

        $status = $_GET['status'] ?? null;
        $model  = new LeaveRequest();
        if (has_role('HRD')) {
            $rows = $model->listForRoles(['Kepsek','Staff','Guru','Security'], $status);
        } elseif (has_role('Supervisor')) {
            $rows = $model->listForRoles(['HRD','Manajerial'], $status);
        } else {
            $rows = $model->listForRoles(['Staff','Guru','Security'], $status);
        }

        return $this->render('verifikasi.index', [
            'title'  => 'Verifikasi Cuti',
            'rows'   => $rows,
            'status' => $status,
        ]);
    }

    /** POST /verifikasi-cuti/{id}/action */
    public function action(string $id = ''): string
    {
        if (!has_role('HRD','Supervisor','Kepsek')) return $this->forbid();

        $model   = new LeaveRequest();
        $userM   = new User();
        $targetId = (int)($_POST['leave_id'] ?? 0);
        if ($targetId <= 0 && !empty($id)) {
            $targetId = (int)$id;
        }
        if ($targetId <= 0 && !empty($_SERVER['REQUEST_URI'])) {
            if (preg_match('#/verifikasi-cuti/(\d+)/action#', $_SERVER['REQUEST_URI'], $matches)) {
                $targetId = (int)$matches[1];
            }
        }
        if ($targetId <= 0) {
            file_put_contents(APP_PATH . '/verif-debug.txt', "BAD_TARGET_ID\nREQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . "\nPOST=" . json_encode($_POST) . "\n\n", FILE_APPEND);
            $this->flash('error', 'Pengajuan tidak ditemukan.');
            return $this->redirect('/verifikasi-cuti');
        }
        $req = $model->findWithUser($targetId);
        if (!$req) {
            file_put_contents(APP_PATH . '/verif-debug.txt', "NOT_FOUND\nTARGET_ID={$targetId}\nREQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . "\nPOST=" . json_encode($_POST) . "\n\n", FILE_APPEND);
            $this->flash('error', 'Pengajuan tidak ditemukan.');
            return $this->redirect('/verifikasi-cuti');
        }

        // Kepsek hanya boleh approve/reject pengajuan non-HRD.
        if (has_role('Kepsek') && !in_array($req['user_role'], ['Staff','Guru','Security'], true)) {
            $this->flash('error', 'Kepsek hanya dapat memverifikasi pengajuan dari role Staff, Guru, atau Security.');
            return $this->redirect('/verifikasi-cuti');
        }

        if (has_role('Supervisor') && !in_array($req['user_role'], ['Manajerial','HRD'], true)) {
            $this->flash('error', 'Supervisor hanya dapat memverifikasi pengajuan dari role HRD atau Manajerial.');
            return $this->redirect('/verifikasi-cuti');
        }

        // HRD hanya dapat memverifikasi pengajuan dari Kepsek, Staff, Guru, atau Security.
        if (has_role('HRD') && !in_array($req['user_role'], ['Kepsek','Staff','Guru','Security'], true)) {
            $this->flash('error', 'HRD hanya dapat memverifikasi pengajuan dari role Kepsek, Staff, Guru, atau Security.');
            return $this->redirect('/verifikasi-cuti');
        }

        $aksi    = $_POST['aksi']    ?? '';
        $catatan = trim((string)($_POST['catatan'] ?? ''));
        if (!in_array($aksi, ['approve','reject'], true)) {
            $this->flash('error', 'Aksi tidak valid.');
            return $this->redirect('/verifikasi-cuti');
        }

        $newStatus = $aksi === 'approve' ? 'approved' : 'rejected';

        // Jika approve & jenis non-sakit → kurangi jumlah_cuti user (selisih hari +1)
        if ($newStatus === 'approved' && $req['status'] !== 'approved' && $req['jenis'] !== 'sakit') {
            $days = max(1, (int)((strtotime($req['tanggal_selesai']) - strtotime($req['tanggal_mulai']))/86400) + 1);
            $sisa = max(0, (int)$req['user_jumlah_cuti'] - $days);
            $userM->update((int)$req['user_id'], ['jumlah_cuti' => $sisa]);
        }

        $model->update($targetId, [
            'status'      => $newStatus,
            'verified_by' => user()['id'],
            'catatan'     => $catatan ?: null,
        ]);

        // Tulis baris izin/sakit ke tabel attendances supaya kolom "Izin"/
        // "Sakit" di laporan benar-benar terisi (lihat Attendance::syncFromApprovedLeave).
        if ($newStatus === 'approved') {
            (new Attendance())->syncFromApprovedLeave($req);
        }

        $this->flash('success', 'Pengajuan ' . ($aksi==='approve'?'disetujui':'ditolak') . '.');
        return $this->redirect('/verifikasi-cuti');
    }

    private function forbid(): string
    {
        http_response_code(403);
        return $this->render('errors.403', ['title'=>'403'], 'auth');
    }
}
