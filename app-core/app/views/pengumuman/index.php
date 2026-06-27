<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="mb-0">Pengumuman</h2>
    <small class="text-muted-soft">Pengumuman yang dipublikasi tampil di dashboard semua karyawan.</small>
  </div>
  <?php if (has_role('HRD','Kepsek')): ?>
    <a href="<?= url('/pengumuman/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Pengumuman</a>
  <?php elseif (has_role('Supervisor')): ?>
    <div class="text-muted-soft">Supervisor hanya dapat melihat daftar pengumuman.</div>
  <?php endif; ?>
</div>

<div class="row g-3">
<?php if (empty($items)): ?>
  <div class="col-12"><div class="empty-state"><i class="bi bi-megaphone"></i><div>Belum ada pengumuman.</div></div></div>
<?php endif; ?>
<?php foreach ($items as $a): ?>
  <div class="col-md-6">
    <div class="card-soft h-100">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h4 class="mb-0"><?= e($a['judul']) ?></h4>
        <?= $a['is_published']
            ? '<span class="badge bg-success-subtle text-success">Published</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Draft</span>' ?>
      </div>
      <p class="text-muted-soft mb-2" style="white-space:pre-line"><?= e(mb_substr($a['isi'], 0, 220)) ?><?= mb_strlen($a['isi'])>220?'…':'' ?></p>
      <div class="small text-muted-soft mb-3">
        <i class="bi bi-person"></i> <?= e($a['creator_nama'] ?? 'Sistem') ?> &middot;
        <i class="bi bi-clock"></i> <?= e(format_date_id($a['created_at'], true)) ?>
      </div>

      <?php if (has_role('HRD','Kepsek')): ?>
        <div class="d-flex gap-2">
          <a href="<?= url('/pengumuman/' . $a['id'] . '/edit') ?>" class="btn btn-light flex-grow-1"><i class="bi bi-pencil"></i> Edit</a>
          <form method="post" action="<?= url('/pengumuman/' . $a['id'] . '/toggle') ?>"><?= csrf_field() ?>
            <button class="btn btn-light"><i class="bi bi-<?= $a['is_published']?'eye-slash':'send' ?>"></i></button>
          </form>
          <form method="post" action="<?= url('/pengumuman/' . $a['id'] . '/delete') ?>" class="form-confirm-delete"><?= csrf_field() ?>
            <button class="btn btn-light text-danger"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
