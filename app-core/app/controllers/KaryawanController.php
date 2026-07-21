<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;

class KaryawanController extends Controller
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
        $users = (new User())->allWithRole();
        $q = trim((string)($_GET['q'] ?? ''));

        // Filter by search query
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $users = array_values(array_filter($users, fn($u) =>
                str_contains(mb_strtolower($u['nama']), $needle) ||
                str_contains(mb_strtolower($u['niy']), $needle)
            ));
        }

        return $this->render('karyawan.index', [
            'title'    => 'Master Karyawan',
            'users'    => $users,
            'q'        => $q,
        ]);
    }

    public function create(): string
    {
        $this->guard();
        return $this->render('karyawan.create', [
            'title'  => 'Tambah Karyawan',
            'roles'  => (new Role())->all('id ASC'),
            'shifts' => (new Shift())->active(),
        ]);
    }

    public function store(): string
    {
        $this->guard();

        $rules = [
            'niy'      => 'required|max:32',
            'nama'     => 'required|max:120',
            'jabatan'  => 'max:120',
            'role_id'  => 'required|integer',
            'email'    => 'max:120',
            'phone'    => 'max:32',
            'password' => 'required|min:6|max:64',
            'shift_id' => 'integer',
            'jumlah_cuti'      => 'integer',
            'latitude_kantor'  => 'max:32',
            'longitude_kantor' => 'max:32',
            'radius_meter'     => 'integer',
        ];
        $v = Validator::make($_POST, $rules);
        $userModel = new User();

        if ($userModel->niyExists($_POST['niy'] ?? '')) {
            $v->addError('niy', 'NIY sudah dipakai karyawan lain.');
        }
        if (!empty($_POST['email']) && $userModel->emailExists($_POST['email'])) {
            $v->addError('email', 'Email sudah dipakai karyawan lain.');
        }

        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Periksa kembali isian yang ditandai.');
            return $this->redirect('/karyawan/create');
        }

        // Upload foto profile
        $fotoName = $this->saveProfilePhoto('foto_profile');

        $data = [
            'niy'              => trim($_POST['niy']),
            'nama'             => trim($_POST['nama']),
            'jabatan'          => trim($_POST['jabatan'] ?? '') ?: null,
            'role_id'          => (int)$_POST['role_id'],
            'email'            => trim($_POST['email'] ?? '') ?: null,
            'phone'            => trim($_POST['phone'] ?? '') ?: null,
            'foto_profile'     => $fotoName,
            'face_descriptor'  => trim($_POST['face_descriptor'] ?? '') ?: null,
            'jumlah_cuti'      => (int)($_POST['jumlah_cuti'] ?? 12),
            'latitude_kantor'  => $_POST['latitude_kantor'] ?? null,
            'longitude_kantor' => $_POST['longitude_kantor'] ?? null,
            'radius_meter'     => (int)($_POST['radius_meter'] ?? 150),
            'password'         => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'is_active'        => 1,
        ];

        $id = $userModel->create($data);

        $shiftIds = $_POST['shift_ids'] ?? (!empty($_POST['shift_id']) ? [$_POST['shift_id']] : []);
        if (!empty($shiftIds)) {
            (new UserShift())->setShifts($id, (array)$shiftIds);
        }

        $this->flash('success', 'Karyawan baru berhasil ditambahkan.');
        return $this->redirect('/karyawan');
    }

    public function show(string $id): string
    {
        $this->guardView();
        $user = (new User())->findWithRole((int)$id);
        if (!$user) return $this->notFound();
        $userShifts = (new UserShift())->shiftsFor((int)$id);
        return $this->render('karyawan.show', [
            'title'  => 'Detail Karyawan',
            'user'   => $user,
            'shift'  => $userShifts[0] ?? null,
            'shifts' => $userShifts,
        ]);
    }

    public function edit(string $id): string
    {
        $this->guard();
        $user = (new User())->find((int)$id);
        if (!$user) return $this->notFound();
        return $this->render('karyawan.edit', [
            'title'   => 'Edit Karyawan',
            'user'    => $user,
            'roles'   => (new Role())->all('id ASC'),
            'shifts'   => (new Shift())->active(),
            'shiftId'  => (new UserShift())->defaultShiftId((int)$id),
            'shiftIds' => (new UserShift())->shiftIdsFor((int)$id),
        ]);
    }

    public function update(string $id): string
    {
        $this->guard();
        $userModel = new User();
        $user = $userModel->find((int)$id);
        if (!$user) return $this->notFound();

        $rules = [
            'niy'     => 'required|max:32',
            'nama'    => 'required|max:120',
            'role_id' => 'required|integer',
        ];
        $v = Validator::make($_POST, $rules);

        if ($userModel->niyExists($_POST['niy'] ?? '', (int)$id)) {
            $v->addError('niy', 'NIY sudah dipakai karyawan lain.');
        }
        if (!empty($_POST['email']) && $userModel->emailExists($_POST['email'], (int)$id)) {
            $v->addError('email', 'Email sudah dipakai karyawan lain.');
        }

        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Periksa kembali isian yang ditandai.');
            return $this->redirect('/karyawan/' . $id . '/edit');
        }

        $data = [
            'niy'              => trim($_POST['niy']),
            'nama'             => trim($_POST['nama']),
            'jabatan'          => trim($_POST['jabatan'] ?? '') ?: null,
            'role_id'          => (int)$_POST['role_id'],
            'email'            => trim($_POST['email'] ?? '') ?: null,
            'phone'            => trim($_POST['phone'] ?? '') ?: null,
            'jumlah_cuti'      => (int)($_POST['jumlah_cuti'] ?? 12),
            'latitude_kantor'  => $_POST['latitude_kantor'] ?? null,
            'longitude_kantor' => $_POST['longitude_kantor'] ?? null,
            'radius_meter'     => (int)($_POST['radius_meter'] ?? 150),
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        // foto baru?
        $newFoto = $this->saveProfilePhoto('foto_profile');
        if ($newFoto) {
            $data['foto_profile'] = $newFoto;
            // hapus lama
            if (!empty($user['foto_profile'])) {
                $old = PUBLIC_PATH . '/uploads/profile/' . $user['foto_profile'];
                if (is_file($old)) @unlink($old);
            }
        }
        // descriptor baru (boleh kosong = pertahankan lama)
        if (!empty($_POST['face_descriptor'])) {
            $data['face_descriptor'] = trim($_POST['face_descriptor']);
        }

        $userModel->update((int)$id, $data);

        $shiftIds = $_POST['shift_ids'] ?? (!empty($_POST['shift_id']) ? [$_POST['shift_id']] : []);
        (new UserShift())->setShifts((int)$id, (array)$shiftIds);

        $this->flash('success', 'Data karyawan berhasil diperbarui.');
        return $this->redirect('/karyawan');
    }

    public function destroy(string $id): string
    {
        $this->guard();
        $userModel = new User();
        $user = $userModel->find((int)$id);
        if (!$user) return $this->notFound();

        // proteksi: jangan hapus diri sendiri
        if ((int)$id === (int)(user()['id'] ?? 0)) {
            $this->flash('error', 'Tidak bisa menghapus akun sendiri.');
            return $this->redirect('/karyawan');
        }

        try {
            $userModel->delete((int)$id);
            if (!empty($user['foto_profile'])) {
                $f = PUBLIC_PATH . '/uploads/profile/' . $user['foto_profile'];
                if (is_file($f)) @unlink($f);
            }
            $this->flash('success', 'Karyawan dihapus.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Gagal menghapus: data terkait masih ada (absensi/cuti).');
        }
        return $this->redirect('/karyawan');
    }

    private function saveProfilePhoto(string $field): ?string
    {
        if (empty($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return null;
        }

        $error = $_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $this->flash('error', 'Foto melebihi 2 MB.');
            }
            return null;
        }

        $cfg = \App\Core\App::$config['upload'] ?? [];
        $max = isset($cfg['profile_max']) ? (int)$cfg['profile_max'] : 2 * 1024 * 1024;
        if (empty($_FILES[$field]['size']) || $_FILES[$field]['size'] > $max) {
            $this->flash('error', 'Foto melebihi 2 MB.');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES[$field]['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            $this->flash('error', 'Format foto harus JPG/PNG/WEBP.');
            return null;
        }

        $name = 'p_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $dest = PUBLIC_PATH . '/uploads/profile/' . $name;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            $this->flash('error', 'Gagal menyimpan foto.');
            return null;
        }
        return $name;
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->view->render('errors/404', ['title' => '404'], 'auth');
    }
}
