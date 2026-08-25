<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\App;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Holiday;

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
        $blocked  = (new LeaveRequest())->existingDatesForUser((int)$u['id']);
        $holidays = array_column((new Holiday())->allOrdered(), 'tanggal');
        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('cuti.create', [
            'title'        => 'Ajukan Cuti / Sakit',
            'me'           => $me,
            'blockedDates' => $blocked,
            'holidayDates' => $holidays,
        ], $layout);
    }

    /** POST /cuti/create */
    public function store(): string
    {
        if ($resp = $this->forbidSupervisorSubmit()) return $resp;
        $userId = (int)user()['id'];
        $jenis  = $_POST['jenis'] ?? '';
        $alasan = trim((string)($_POST['alasan'] ?? ''));

        $rawDates = $_POST['tanggal'] ?? [];
        $dates = [];
        if (is_array($rawDates)) {
            foreach (array_unique($rawDates) as $d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) {
                    $dates[] = $d;
                }
            }
        }
        sort($dates);

        $notes = $this->parseDateNotes($_POST['catatan_tanggal'] ?? []);

        $v = Validator::make($_POST, [
            'jenis'  => 'required',
            'alasan' => 'required|min:5|max:1000',
        ]);
        if (empty($dates)) {
            $v->addError('tanggal', 'Pilih minimal satu tanggal di kalender.');
        }
        if (!in_array($jenis, ['sakit','tahunan','melahirkan','menikah'], true)) {
            $v->addError('jenis', 'Jenis cuti tidak valid.');
        }

        // Jangan sampai tanggal yg dipilih tabrakan dgn cuti lain yg masih
        // pending/approved milik karyawan yg sama (cegah duplikat tanggal).
        $blocked = (new LeaveRequest())->existingDatesForUser($userId);
        $clash = array_values(array_intersect($dates, array_keys($blocked)));
        if (!empty($clash)) {
            $v->addError('tanggal', 'Tanggal ' . implode(', ', $clash) . ' sudah dipakai cuti lain.');
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

        // SATU pengajuan = SATU baris leave_requests, walau tanggalnya
        // tidak berurutan — tanggal individualnya (+ catatan per-tanggal
        // kalau diisi) disimpan di leave_request_dates.
        $dateMap = [];
        foreach ($dates as $d) {
            $dateMap[$d] = $notes[$d] ?? null;
        }

        (new LeaveRequest())->createWithDates([
            'user_id'    => $userId,
            'jenis'      => $jenis,
            'alasan'     => $alasan,
            'file_surat' => $filePath,
            'status'     => 'pending',
        ], $dateMap);

        unset($_SESSION['_old'], $_SESSION['_errors']);
        $this->flash('success', 'Pengajuan cuti berhasil dikirim. Menunggu verifikasi.');
        return $this->redirect('/cuti');
    }

<<<<<<< HEAD
=======
    /** GET /cuti/manual — HRD: lihat riwayat & input cuti baru langsung utk satu karyawan */
    public function manualCreate(): string
    {
        $userModel = new User();
        $karyawan  = $userModel->allWithRole();

        $selectedUserId = (int)($_GET['user_id'] ?? 0);
        $selectedUser   = null;
        $existingLeaves = [];
        $blocked        = [];

        if ($selectedUserId) {
            $selectedUser = $userModel->find($selectedUserId);
            if ($selectedUser) {
                $leaveModel     = new LeaveRequest();
                $existingLeaves = $leaveModel->listFor($selectedUserId);
                $blocked        = $leaveModel->existingDatesForUser($selectedUserId);
            } else {
                $selectedUserId = 0;
            }
        }

        $holidays = array_column((new Holiday())->allOrdered(), 'tanggal');

        return $this->render('cuti.manual', [
            'title'          => 'Input Cuti Manual',
            'karyawan'       => $karyawan,
            'selectedUserId' => $selectedUserId,
            'selectedUser'   => $selectedUser,
            'existingLeaves' => $existingLeaves,
            'blockedDates'   => $blocked,
            'holidayDates'   => $holidays,
        ]);
    }

    /** POST /cuti/manual — buat cuti baru utk karyawan terpilih, langsung berstatus disetujui */
    public function manualStore(): string
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $jenis  = $_POST['jenis'] ?? '';
        $alasan = trim((string)($_POST['alasan'] ?? ''));

        $rawDates = $_POST['tanggal'] ?? [];
        $dates = [];
        if (is_array($rawDates)) {
            foreach (array_unique($rawDates) as $d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) {
                    $dates[] = $d;
                }
            }
        }
        sort($dates);

        $notes = $this->parseDateNotes($_POST['catatan_tanggal'] ?? []);

        $v = Validator::make($_POST, [
            'user_id' => 'required',
            'jenis'   => 'required',
        ]);
        if (empty($dates)) {
            $v->addError('tanggal', 'Pilih minimal satu tanggal di kalender.');
        }
        if (!in_array($jenis, ['sakit','tahunan','melahirkan','menikah','darurat'], true)) {
            $v->addError('jenis', 'Jenis cuti tidak valid.');
        }

        $userModel = new User();
        $target    = $userId ? $userModel->find($userId) : null;
        if (!$target) {
            $v->addError('user_id', 'Karyawan tidak ditemukan.');
        }

        $leaveModel = new LeaveRequest();
        if ($userId) {
            $blocked = $leaveModel->existingDatesForUser($userId);
            $clash = array_values(array_intersect($dates, array_keys($blocked)));
            if (!empty($clash)) {
                $v->addError('tanggal', 'Tanggal ' . implode(', ', $clash) . ' sudah dipakai cuti lain.');
            }
        }

        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Periksa kembali isian Anda.');
            return $this->redirect('/cuti/manual' . ($userId ? '?user_id=' . $userId : ''));
        }

        // Surat opsional (situasi mendadak biasanya belum ada dokumen resmi)
        $filePath = null;
        if (!empty($_FILES['file_surat']['name'])) {
            $filePath = $this->saveDocument($_FILES['file_surat']);
            if (!$filePath) {
                $this->flash('error', 'File surat tidak valid (PDF/JPG/PNG max 5 MB).');
                return $this->redirect('/cuti/manual?user_id=' . $userId);
            }
        }

        $catatan = 'Diinput manual oleh ' . (user()['nama'] ?? 'HRD');

        $dateMap = [];
        foreach ($dates as $d) {
            $dateMap[$d] = $notes[$d] ?? null;
        }

        $leaveId = $leaveModel->createWithDates([
            'user_id'     => $userId,
            'jenis'       => $jenis,
            'alasan'      => $alasan !== '' ? $alasan : '(tidak ada keterangan)',
            'file_surat'  => $filePath,
            'status'      => 'approved',
            'verified_by' => user()['id'],
            'catatan'     => $catatan,
        ], $dateMap);

        // Langsung disetujui -> potong jatah cuti tahunan kecuali 'sakit', lalu tulis attendance.
        if ($jenis !== 'sakit') {
            $sisa = max(0, (int)$target['jumlah_cuti'] - count($dates));
            $userModel->update($userId, ['jumlah_cuti' => $sisa]);
        }
        $leave = $leaveModel->find($leaveId);
        if ($leave) {
            (new Attendance())->syncFromApprovedLeave($leave, $dates);
        }

        unset($_SESSION['_old'], $_SESSION['_errors']);
        $n = count($dates);
        $this->flash('success', "Cuti manual berhasil diinput untuk {$n} hari dan langsung disetujui.");
        return $this->redirect('/cuti/manual?user_id=' . $userId);
    }

    /** GET /cuti/{id}/lihat — lihat detail cuti (read-only). Pemilik cuti sendiri, atau role verifikator. */
    public function show(string $id): string
    {
        $leaveModel = new LeaveRequest();
        $row = $leaveModel->findWithUser((int)$id);
        if (!$row) {
            $this->flash('error', 'Data cuti tidak ditemukan.');
            return $this->redirect('/cuti');
        }

        $isOwner = (int)$row['user_id'] === (int)user()['id'];
        if (!$isOwner && !has_role('HRD', 'Supervisor', 'Kepsek')) {
            http_response_code(403);
            return $this->render('errors.403', ['title' => '403'], 'auth');
        }

        $dates = $leaveModel->datesFor((int)$id);
        $layout = is_pegawai() ? 'mobile' : 'app';

        return $this->render('cuti.show', [
            'title' => 'Detail Cuti',
            'row'   => $row,
            'dates' => $dates,
        ], $layout);
    }

>>>>>>> 95eafbc (update)
    /** GET /cuti/{id}/edit — HRD ubah tanggal/jenis cuti yg sudah ada (utk perbaiki kesalahan input) */
    public function editForm(string $id): string
    {
        $leaveModel = new LeaveRequest();
        $row = $leaveModel->findWithUser((int)$id);
        if (!$row) {
            $this->flash('error', 'Data cuti tidak ditemukan.');
            return $this->redirect('/verifikasi-cuti');
        }

        $existingRows  = $leaveModel->datesFor((int)$id);
        $existingDates = array_column($existingRows, 'tanggal');
        $existingNotes = [];
        foreach ($existingRows as $r) {
            if (!empty($r['keterangan'])) $existingNotes[$r['tanggal']] = $r['keterangan'];
        }

        $holidays = array_column((new Holiday())->allOrdered(), 'tanggal');
        $blocked  = $leaveModel->existingDatesForUser((int)$row['user_id'], (int)$id);

        return $this->render('cuti.edit', [
            'title'         => 'Ubah Cuti',
            'row'           => $row,
            'existingDates' => $existingDates,
            'existingNotes' => $existingNotes,
            'holidayDates'  => $holidays,
            'blockedDates'  => $blocked,
        ]);
    }

    /** POST /cuti/{id}/edit */
    public function update(string $id): string
    {
        $leaveModel = new LeaveRequest();
        $userModel  = new User();
        $attModel   = new Attendance();

        $old = $leaveModel->findWithUser((int)$id);
        if (!$old) {
            $this->flash('error', 'Data cuti tidak ditemukan.');
            return $this->redirect('/verifikasi-cuti');
        }

        // Karyawan dikunci — edit ini utk pindah TANGGAL/JENIS, bukan
        // memindahkan cuti ke karyawan lain.
        $userId = (int)$old['user_id'];
        $jenis  = $_POST['jenis'] ?? '';
        $alasan = trim((string)($_POST['alasan'] ?? ''));

        $rawDates = $_POST['tanggal'] ?? [];
        $dates = [];
        if (is_array($rawDates)) {
            foreach (array_unique($rawDates) as $d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) {
                    $dates[] = $d;
                }
            }
        }
        sort($dates);

        $notes = $this->parseDateNotes($_POST['catatan_tanggal'] ?? []);

        $v = Validator::make($_POST, [
            'jenis'  => 'required',
            'alasan' => 'max:1000',
        ]);
        if (empty($dates)) {
            $v->addError('tanggal', 'Pilih minimal satu tanggal di kalender.');
        }
        if (!in_array($jenis, ['sakit','tahunan','melahirkan','menikah','darurat'], true)) {
            $v->addError('jenis', 'Jenis cuti tidak valid.');
        }

        // Cegah tanggal hasil edit tabrakan dgn cuti LAIN milik karyawan yg
        // sama (baris yg sedang diedit sendiri dikecualikan).
        $blocked = $leaveModel->existingDatesForUser($userId, (int)$id);
        $clash = array_values(array_intersect($dates, array_keys($blocked)));
        if (!empty($clash)) {
            $v->addError('tanggal', 'Tanggal ' . implode(', ', $clash) . ' sudah dipakai cuti lain milik karyawan ini.');
        }

        if ($v->fails()) {
            $_SESSION['_old']    = $_POST;
            $_SESSION['_errors'] = $v->errors();
            $this->flash('error', 'Periksa kembali isian Anda.');
            return $this->redirect('/cuti/' . $id . '/edit');
        }

        // Kalau data lama berstatus approved, balikkan dulu efeknya (kuota
        // + baris attendance) SEBELUM menulis yg baru — pakai tanggal LAMA
        // yg SEBENARNYA dipilih (leave_request_dates), bukan rentang
        // tanggal_mulai..tanggal_selesai (bisa salah kalau tanggalnya dulu
        // tidak berurutan).
        $oldDates = array_column($leaveModel->datesFor((int)$id), 'tanggal');
        if ($old['status'] === 'approved') {
            if ($old['jenis'] !== 'sakit') {
                $oldDays = max(1, count($oldDates));
                $user = $userModel->find($userId);
                if ($user) {
                    $userModel->update($userId, ['jumlah_cuti' => max(0, (int)$user['jumlah_cuti'] + $oldDays)]);
                }
            }
            $attModel->deleteLeaveRowsForApprovedLeave($old, $oldDates);
        }

        // File baru opsional — kalau tidak diupload ulang, file lama tetap dipakai.
        $filePath = $old['file_surat'] ?? null;
        if (!empty($_FILES['file_surat']['name'])) {
            $newFile = $this->saveDocument($_FILES['file_surat']);
            if (!$newFile) {
                $this->flash('error', 'File surat tidak valid (PDF/JPG/PNG max 5 MB).');
                return $this->redirect('/cuti/' . $id . '/edit');
            }
            if ($filePath) {
                $oldFull = PUBLIC_PATH . '/uploads/' . $filePath;
                if (is_file($oldFull)) @unlink($oldFull);
            }
            $filePath = $newFile;
        }

        $catatan = trim(($old['catatan'] ? $old['catatan'] . ' ' : '') . '(diubah oleh ' . (user()['nama'] ?? 'HRD') . ')');

        // SATU baris TETAP SATU baris — tanggal-tanggalnya diganti
        // seluruhnya (bisa nambah/kurang/pindah), tidak pernah dipecah
        // jadi baris leave_requests baru walau hasilnya tidak berurutan.
        $dateMap = [];
        foreach ($dates as $d) {
            $dateMap[$d] = $notes[$d] ?? null;
        }
        $leaveModel->updateWithDates((int)$id, [
            'jenis'      => $jenis,
            'alasan'     => $alasan !== '' ? $alasan : '(tidak ada keterangan)',
            'file_surat' => $filePath,
            'catatan'    => $catatan,
        ], $dateMap);

        if ($old['status'] === 'approved') {
            $updated = $leaveModel->find((int)$id);
            $attModel->syncFromApprovedLeave($updated, $dates);

            if ($jenis !== 'sakit') {
                $user = $userModel->find($userId);
                $sisa = max(0, (int)$user['jumlah_cuti'] - count($dates));
                $userModel->update($userId, ['jumlah_cuti' => $sisa]);
            }
        }

        unset($_SESSION['_old'], $_SESSION['_errors']);
        $this->flash('success', 'Cuti berhasil diubah.');
        return $this->redirect('/verifikasi-cuti');
    }

    public function destroy(string $id): string
    {
        $model = new LeaveRequest();
        $row   = $model->find((int)$id);
        if (!$row) {
            $this->flash('error', 'Pengajuan cuti tidak ditemukan.');
            return $this->redirect('/cuti');
        }

        if (!has_role('HRD')) {
            http_response_code(403);
            return $this->render('errors.403', ['title' => '403'], 'auth');
        }

        $redirect = '/cuti';
        if (!empty($_POST['redirect_to'])) {
            $redirectTo = $_POST['redirect_to'];
            if (str_starts_with($redirectTo, '/')) {
                $redirect = $redirectTo;
            } elseif (filter_var($redirectTo, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($redirectTo);
                if (!empty($parsed['path'])) {
                    $redirect = $parsed['path'];
                    if (!empty($parsed['query'])) {
                        $redirect .= '?' . $parsed['query'];
                    }
                }
            }
        }

        if ($row['status'] === 'approved') {
            $dates = array_column($model->datesFor((int)$id), 'tanggal');
            if ($row['jenis'] !== 'sakit') {
                $days = max(1, count($dates));
                $userModel = new User();
                $user = $userModel->find((int)$row['user_id']);
                if ($user) {
                    $userModel->update((int)$row['user_id'], [
                        'jumlah_cuti' => max(0, (int)$user['jumlah_cuti'] + $days),
                    ]);
                }
            }

            (new Attendance())->deleteLeaveRowsForApprovedLeave($row, $dates);
        }

        if (!empty($row['file_surat'])) {
            $filePath = PUBLIC_PATH . '/uploads/' . $row['file_surat'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        $model->delete((int)$id); // leave_request_dates ikut terhapus via ON DELETE CASCADE
        $this->flash('success', 'Pengajuan cuti berhasil dihapus.');
        return $this->redirect($redirect);
    }

    /** Ubah $_POST['catatan_tanggal'] (assoc: tanggal => teks) jadi array bersih, trim + potong 255 char. */
    private function parseDateNotes($raw): array
    {
        $notes = [];
        if (is_array($raw)) {
            foreach ($raw as $d => $n) {
                $n = trim((string)$n);
                if ($n !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $notes[$d] = mb_substr($n, 0, 255);
                }
            }
        }
        return $notes;
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
