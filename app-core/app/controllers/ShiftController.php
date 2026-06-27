<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Shift;

class ShiftController extends Controller
{
    private function guard(): void
    {
        if (!has_role('HRD')) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['title' => '403'], 'auth');
            exit;
        }
    }

    private function guardView(): void
    {
        if (!has_role('HRD', 'Supervisor')) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['title' => '403'], 'auth');
            exit;
        }
    }

    public function index(): string
    {
        $this->guardView();
        return $this->render('shift.index', [
            'title'  => 'Master Shift Kerja',
            'shifts' => (new Shift())->all('jam_masuk ASC'),
        ]);
    }

    public function store(): string
    {
        $this->guard();
        $rules = [
            'nama'            => 'required|max:64',
            'jam_masuk'       => 'required',
            'jam_keluar'      => 'required',
            'toleransi_menit' => 'required|integer',
            'cut_off_tanggal' => 'required|integer',
        ];
        $v = Validator::make($_POST, $rules);
        if ($v->fails()) {
            $this->flash('error', 'Lengkapi data shift.');
            return $this->redirect('/shift');
        }
        (new Shift())->create([
            'nama'            => trim($_POST['nama']),
            'jam_masuk'       => $_POST['jam_masuk'],
            'jam_keluar'      => $_POST['jam_keluar'],
            'toleransi_menit' => (int)$_POST['toleransi_menit'],
            'cut_off_tanggal' => (int)$_POST['cut_off_tanggal'],
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);
        $this->flash('success', 'Shift baru ditambahkan.');
        return $this->redirect('/shift');
    }

    public function update(string $id): string
    {
        $this->guard();
        $m = new Shift();
        if (!$m->find((int)$id)) return $this->redirect('/shift');
        $m->update((int)$id, [
            'nama'            => trim($_POST['nama']),
            'jam_masuk'       => $_POST['jam_masuk'],
            'jam_keluar'      => $_POST['jam_keluar'],
            'toleransi_menit' => (int)$_POST['toleransi_menit'],
            'cut_off_tanggal' => (int)$_POST['cut_off_tanggal'],
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);
        $this->flash('success', 'Shift diperbarui.');
        return $this->redirect('/shift');
    }

    public function destroy(string $id): string
    {
        $this->guard();
        $m = new Shift();
        if ($m->inUse((int)$id)) {
            $this->flash('error', 'Shift sedang dipakai karyawan, tidak bisa dihapus.');
            return $this->redirect('/shift');
        }
        $m->delete((int)$id);
        $this->flash('success', 'Shift dihapus.');
        return $this->redirect('/shift');
    }
}
