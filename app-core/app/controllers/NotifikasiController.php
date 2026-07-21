<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Announcement;
use App\Models\NotificationRead;

class NotifikasiController extends Controller
{
    /** GET /notifikasi — halaman list (mobile-first) */
    public function index(): string
    {
        $u    = user();
        $repo = new NotificationRead();
        $feed = $repo->feedFor((int)$u['id']);

        $layout = is_pegawai() ? 'mobile' : 'app';
        return $this->render('notifikasi.index', [
            'title' => 'Notifikasi',
            'feed'  => $feed,
        ], $layout);
    }

    public function show(string $type, string $id): string
    {
        $u = user();
        $nid = (int)$id;
        if (!in_array($type, ['announcement', 'leave_status'], true) || $nid <= 0) {
            return $this->notFound();
        }

        $repo = new NotificationRead();
        $repo->markRead((int)$u['id'], $type, $nid);

        $layout = is_pegawai() ? 'mobile' : 'app';

        if ($type === 'announcement') {
            $announcement = (new Announcement())->find($nid);
            if (!$announcement || !$announcement['is_published']) {
                return $this->notFound();
            }

            return $this->render('notifikasi.show', [
                'title' => 'Detail Notifikasi',
                'type'  => 'announcement',
                'item'  => $announcement,
            ], $layout);
        }

        $stmt = Database::pdo()->prepare(
            'SELECT lr.*, v.nama AS verifier_nama, u.nama AS requester_nama
             FROM leave_requests lr
             LEFT JOIN users v ON v.id = lr.verified_by
             LEFT JOIN users u ON u.id = lr.user_id
             WHERE lr.id = ? LIMIT 1'
        );
        $stmt->execute([$nid]);
        $leave = $stmt->fetch();
        if (!$leave || (int)$leave['user_id'] !== (int)$u['id']) {
            return $this->notFound();
        }

        return $this->render('notifikasi.show', [
            'title' => 'Detail Notifikasi',
            'type'  => 'leave_status',
            'item'  => $leave,
        ], $layout);
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->view->render('errors/404', ['title' => '404'], 'auth');
    }

    /** POST /notifikasi/read-all — tandai semua sudah dibaca */
    public function readAll(): string
    {
        $u    = user();
        $repo = new NotificationRead();
        $feed = $repo->feedFor((int)$u['id']);
        $items = [];
        foreach ($feed as $n) {
            if (!$n['is_read']) $items[] = [$n['type'], $n['ref_id']];
        }
        $repo->markAll((int)$u['id'], $items);
        $this->flash('success', 'Semua notifikasi ditandai sudah dibaca.');
        return $this->redirect('/notifikasi');
    }

    /** POST /notifikasi/read — single mark-as-read (AJAX optional) */
    public function read(): string
    {
        $u    = user();
        $type = (string)$this->input('type', '');
        $id   = (int)$this->input('id', 0);
        if (!in_array($type, ['announcement','leave_status'], true) || $id <= 0) {
            return $this->json(['ok' => false], 400);
        }
        (new NotificationRead())->markRead((int)$u['id'], $type, $id);
        return $this->json(['ok' => true]);
    }
}
