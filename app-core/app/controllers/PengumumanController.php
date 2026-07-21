<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Announcement;

class PengumumanController extends Controller
{
    private function guard(): void
    {
        if (!has_role('HRD', 'Kepsek')) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['title' => '403'], 'auth');
            exit;
        }
    }

    private function guardView(): void
    {
        if (!has_role('HRD', 'Kepsek', 'Supervisor')) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['title' => '403'], 'auth');
            exit;
        }
    }

    public function index(): string
    {
        $this->guardView();
        return $this->render('pengumuman.index', [
            'title' => 'Pengumuman',
            'items' => (new Announcement())->allWithCreator(),
        ]);
    }

    public function create(): string
    {
        $this->guard();
        return $this->render('pengumuman.create', [
            'title' => 'Tambah Pengumuman',
        ]);
    }

    private function saveAnnouncementImage(string $field)
    {
        if (empty($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return null;
        }

        $error = $_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $this->flash('error', 'Gambar pengumuman melebihi 2 MB.');
            }
            return false;
        }

        $max = 2 * 1024 * 1024;
        if (empty($_FILES[$field]['size']) || $_FILES[$field]['size'] > $max) {
            $this->flash('error', 'Gambar pengumuman melebihi 2 MB.');
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES[$field]['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            $this->flash('error', 'Format gambar harus JPG/PNG/WEBP.');
            return false;
        }

        $dir = PUBLIC_PATH . '/uploads/announcements';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->flash('error', 'Gagal membuat folder upload.');
            return false;
        }

        $name = 'ann_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            $this->flash('error', 'Gagal mengunggah gambar.');
            return false;
        }

        return $name;
    }

    public function store(): string
    {
        $this->guard();
        $v = Validator::make($_POST, [
            'judul' => 'required|max:180',
            'isi'   => 'required',
        ]);
        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Lengkapi judul & isi pengumuman.');
            return $this->redirect('/pengumuman/create');
        }

        $image = $this->saveAnnouncementImage('image');
        if ($image === false) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            return $this->redirect('/pengumuman/create');
        }

        (new Announcement())->create([
            'judul'        => trim($_POST['judul']),
            'isi'          => trim($_POST['isi']),
            'image'        => $image,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'created_by'   => (int)(user()['id'] ?? 0) ?: null,
        ]);
        $this->flash('success', 'Pengumuman ditambahkan.');
        return $this->redirect('/pengumuman');
    }

    public function edit(string $id): string
    {
        $this->guard();
        $m = (new Announcement())->find((int)$id);
        if (!$m) return $this->redirect('/pengumuman');
        return $this->render('pengumuman.edit', [
            'title' => 'Edit Pengumuman',
            'item'  => $m,
        ]);
    }

    public function update(string $id): string
    {
        $this->guard();
        $am = new Announcement();
        $item = $am->find((int)$id);
        if (!$item) return $this->redirect('/pengumuman');

        $image = $this->saveAnnouncementImage('image');
        if ($image === false) {
            return $this->redirect('/pengumuman/' . $id . '/edit');
        }

        if ($image && !empty($item['image'])) {
            $old = PUBLIC_PATH . '/uploads/announcements/' . $item['image'];
            if (is_file($old)) @unlink($old);
        }

        $data = [
            'judul'        => trim($_POST['judul']),
            'isi'          => trim($_POST['isi']),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
        if ($image) {
            $data['image'] = $image;
        }

        $am->update((int)$id, $data);
        $this->flash('success', 'Pengumuman diperbarui.');
        return $this->redirect('/pengumuman');
    }

    public function toggle(string $id): string
    {
        $this->guard();
        (new Announcement())->togglePublish((int)$id);
        $this->flash('success', 'Status publikasi diubah.');
        return $this->redirect('/pengumuman');
    }

    public function destroy(string $id): string
    {
        $this->guard();
        (new Announcement())->delete((int)$id);
        $this->flash('success', 'Pengumuman dihapus.');
        return $this->redirect('/pengumuman');
    }
}
