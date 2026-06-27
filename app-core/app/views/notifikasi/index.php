<?php
  $unread = array_filter($feed, fn($n) => !$n['is_read']);
  $hasUnread = count($unread) > 0;
?>

<div class="page-head d-flex align-items-center justify-content-between mb-3">
  <div>
    <h2 class="mb-0" style="font-size:1.25rem">Notifikasi</h2>
    <div class="text-muted-soft" style="font-size:.82rem">
      <?= count($feed) ?> total · <?= count($unread) ?> belum dibaca
    </div>
  </div>
  <?php if ($hasUnread): ?>
    <form method="post" action="<?= url('/notifikasi/read-all') ?>">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-primary">
        <i class="bi bi-check2-all"></i> Tandai semua
      </button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$feed): ?>
  <div class="card-soft text-center" style="padding:2.5rem 1rem">
    <div style="font-size:2.5rem;color:var(--text-muted)"><i class="bi bi-bell-slash"></i></div>
    <div class="mt-2 fw-semibold">Belum ada notifikasi</div>
    <div class="text-muted-soft" style="font-size:.85rem">
      Pengumuman dari HRD dan status pengajuan cuti akan muncul di sini.
    </div>
  </div>
<?php else: ?>
  <div class="notif-list">
    <?php foreach ($feed as $n): ?>
      <a class="notif-item <?= $n['is_read'] ? 'is-read' : 'is-unread' ?> tone-<?= e($n['tone']) ?>"
         href="<?= e($n['url'] ?? url('/notifikasi')) ?>">
        <div class="ico"><i class="bi <?= e($n['icon']) ?>"></i></div>
        <div class="body">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="title"><?= e($n['judul']) ?></div>
            <?php if (!$n['is_read']): ?><span class="dot" aria-label="belum dibaca"></span><?php endif; ?>
          </div>
          <div class="msg"><?= e(mb_strimwidth($n['isi'], 0, 160, '…')) ?></div>
          <div class="meta"><?= e(format_date_id($n['created_at'], true)) ?></div>
        </div>
        <?php if (!empty($n['image'])): ?>
          <div class="thumb">
            <img src="<?= upload_url('announcements/' . $n['image']) ?>" alt="Preview notifikasi" loading="lazy">
          </div>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
