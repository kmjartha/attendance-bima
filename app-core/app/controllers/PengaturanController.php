<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Holiday;

class PengaturanController extends Controller
{
    private function guard(): void
    {
        if (!has_role('HRD')) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['title' => '403'], 'auth');
            exit;
        }
    }

    /** GET /pengaturan/libur — kalender hari libur (nasional + Bali) */
    public function libur(): string
    {
        $this->guard();
        $year = (int)($_GET['year'] ?? date('Y'));
        $holidayModel = new Holiday();
        return $this->render('pengaturan.libur', [
            'title'    => 'Kalender Hari Libur',
            'holidays' => $holidayModel->allOrdered($year),
            'years'    => $holidayModel->availableYears(),
            'year'     => $year,
        ]);
    }

    /** POST /pengaturan/libur/create */
    public function storeLibur(): string
    {
        $this->guard();
        $tanggal    = trim((string)($_POST['tanggal'] ?? ''));
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $tipe       = $_POST['tipe'] ?? 'nasional';

        if ($tanggal === '' || $keterangan === '') {
            $this->flash('error', 'Tanggal dan keterangan wajib diisi.');
            return $this->redirect('/pengaturan/libur');
        }
        if (!in_array($tipe, ['nasional', 'cuti_bersama', 'sekolah'], true)) {
            $tipe = 'nasional';
        }

        try {
            (new Holiday())->create([
                'tanggal'    => $tanggal,
                'keterangan' => $keterangan,
                'tipe'       => $tipe,
                'created_by' => user()['id'],
            ]);
            $this->flash('success', 'Hari libur berhasil ditambahkan.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Tanggal tersebut sudah ada di kalender libur.');
        }

        return $this->redirect('/pengaturan/libur?year=' . (int)date('Y', strtotime($tanggal)));
    }

    /** POST /pengaturan/libur/{id}/delete */
    public function destroyLibur(string $id): string
    {
        $this->guard();
        (new Holiday())->delete((int)$id);
        $this->flash('success', 'Hari libur dihapus dari kalender.');
        return $this->redirect('/pengaturan/libur');
    }
}
