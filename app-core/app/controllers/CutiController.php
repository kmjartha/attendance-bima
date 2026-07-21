<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\App;
use App\Models\LeaveRequest;
use App\Models\User;

class CutiController extends Controller
{
    private function forbidSupervisor(): string
    {
        if (!has_role('Supervisor')) return '';
        http_response_code(403);
        return $this->render('errors.403', ['title' => '403'], 'auth');
    }

    private function forbidSupervisorSubmit(): string
    {
        if (!has_role('Supervisor')) return '';
        http_response_code(403);
        return $this->render('errors.403', ['title' => '403'], 'auth');
    }

    /** GET /cuti — riwayat pengajuan personal + tombol ajukan */
    public function index(): string
    {
        $u = user();
        $rows = (new LeaveRequest())->listFor((int)$u['id']);
        $me   = (new User())->find((int)$u['id']);
        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('cuti.index', [
            'title' => 'Cuti / Sakit',
            'rows'  => $rows,
            'me'    => $me,
        ], $layout);
    }

    /** GET /cuti/create */
    public function create(): string
    {
        if ($resp = $this->forbidSupervisorSubmit()) return $resp;
        $u  = user();
        $me = (new User())->find((int)$u['id']);
        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('cuti.create', [
            'title' => 'Ajukan Cuti / Sakit',
            'me'    => $me,
        ], $layout);
    }

    /** POST /cuti/create */
    public function store(): string
    {
        if ($resp = $this->forbidSupervisorSubmit()) return $resp;
        $jenis  = $_POST['jenis'] ?? '';
        $start  = $_POST['tanggal_mulai'] ?? '';
        $end    = $_POST['tanggal_selesai'] ?? '';
        $alasan = trim((string)($_POST['alasan'] ?? ''));

        $v = Validator::make($_POST, [
            'jenis'           => 'required',
            'tanggal_mulai'   => 'required',
            'tanggal_selesai' => 'required',
            'alasan'          => 'required|min:5|max:1000',
        ]);
        if (!in_array($jenis, ['sakit','tahunan','melahirkan','menikah'], true)) {
            $v->addError('jenis', 'Jenis cuti tidak valid.');
        }
        if ($start && $end && strtotime($end) < strtotime($start)) {
            $v->addError('tanggal_selesai', 'Tanggal selesai harus ≥ tanggal mulai.');
        }
        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Periksa kembali isian Anda.');
            return $this->redirect('/cuti/create');
        }

        // Upload surat (wajib utk Sakit)
        $filePath = null;
        if (!empty($_FILES['file_surat']['name'])) {
            $filePath = $this->saveDocument($_FILES['file_surat']);
            if (!$filePath) {
                $this->flash('error', 'File surat tidak valid (PDF/JPG/PNG max 5 MB).');
                return $this->redirect('/cuti/create');
            }
        }
        if ($jenis === 'sakit' && !$filePath) {
            $this->flash('error', 'Cuti sakit wajib melampirkan surat dokter.');
            return $this->redirect('/cuti/create');
        }

        (new LeaveRequest())->create([
            'user_id'         => user()['id'],
            'jenis'           => $jenis,
            'tanggal_mulai'   => $start,
            'tanggal_selesai' => $end,
            'alasan'          => $alasan,
            'file_surat'      => $filePath,
            'status'          => 'pending',
        ]);

        unset($_SESSION['_old'], $_SESSION['_errors']);
        $this->flash('success', 'Pengajuan cuti berhasil dikirim. Menunggu verifikasi.');
        return $this->redirect('/cuti');
    }

    private function saveDocument(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $max = (int)(App::$config['upload']['document_max'] ?? 5*1024*1024);
        if ($file['size'] > $max) return null;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $okMime = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/jpg'       => 'jpg',
            'image/pjpeg'     => 'jpg',
            'image/png'       => 'png',
        ];
        if (!isset($okMime[$mime])) return null;
        $dir = UPLOADS_PATH . '/documents';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $okMime[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return null;
        return 'documents/' . $name;
    }
}
