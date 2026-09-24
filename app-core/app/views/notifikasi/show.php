<div class="page-head d-flex align-items-center gap-3 mb-3">
  <a href="<?= url('/notifikasi') ?>" class="btn btn-light btn-icon"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h2 class="mb-1">Detail Notifikasi</h2>
    <div class="text-muted-soft" style="font-size:.85rem">
      <?php if ($type === 'announcement'): ?>Pengumuman resmi dari HRD/Kepsek
      <?php elseif ($type === 'leave_deduction'): ?>Pemotongan jatah cuti oleh HRD
      <?php else: ?>Status pengajuan cuti Anda<?php endif; ?>
    </div>
  </div>
</div>

<div class="card-soft">
  <?php if ($type === 'announcement'): ?>
    <?php if (!empty($item['image'])): ?>
      <div class="notif-detail-image mb-3">
        <img src="<?= upload_url('announcements/' . $item['image']) ?>" alt="<?= e($item['judul']) ?>">
      </div>
    <?php endif; ?>

    <div class="text-muted-soft" style="font-size:.85rem; margin-bottom:.75rem;">
      <?= e(format_date_id($item['created_at'], true)) ?>
    </div>
    <h3 class="mb-3"><?= e($item['judul']) ?></h3>
    <div class="text-muted-soft" style="white-space:pre-line; line-height:1.7;">
      <?= nl2br(e($item['isi'])) ?>
    </div>
  <?php elseif ($type === 'leave_deduction'): ?>
    <div class="d-flex flex-column gap-2 mb-3">
      <div class="text-muted-soft" style="font-size:.85rem;"><?= e(format_date_id($item['created_at'], true)) ?></div>
      <h3 class="mb-0">Jatah Cuti Dikurangi</h3>
      <div class="badge bg-warning text-dark" style="width:max-content; font-size:.78rem; padding:.4rem .75rem; border-radius:999px;">
        −<?= (int)$item['jumlah_hari'] ?> HARI
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="form-field">
          <label class="text-muted-soft">Jumlah Dipotong</label>
          <div class="form-control-clean" style="background: var(--surface-2);"><?= (int)$item['jumlah_hari'] ?> hari</div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="form-field">
          <label class="text-muted-soft">Sisa Cuti</label>
          <div class="form-control-clean" style="background: var(--surface-2);">
            <?= (int)$item['sisa_sebelum'] ?> → <strong><?= (int)$item['sisa_sesudah'] ?> hari</strong>
          </div>
        </div>
      </div>
      <?php if (!empty($item['hr_nama'])): ?>
        <div class="col-12 col-md-6">
          <div class="form-field">
            <label class="text-muted-soft">Dilakukan oleh</label>
            <div class="form-control-clean" style="background: var(--surface-2);"><?= e($item['hr_nama']) ?></div>
          </div>
        </div>
      <?php endif; ?>
      <div class="col-12">
        <div class="form-field">
          <label class="text-muted-soft">Alasan</label>
          <div class="form-control-clean" style="background: var(--surface-2); white-space:pre-line;">
            <?= nl2br(e($item['alasan'])) ?>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-2 mb-3">
      <div class="text-muted-soft" style="font-size:.85rem;"><?= e(format_date_id($item['updated_at'], true)) ?></div>
      <h3 class="mb-0"><?= e('Pengajuan Cuti ' . ($item['status'] === 'approved' ? 'Disetujui' : 'Ditolak')) ?></h3>
      <div class="badge bg-<?= $item['status'] === 'approved' ? 'success' : 'danger' ?> text-white" style="width:max-content; font-size:.78rem; padding:.4rem .75rem; border-radius:999px;">
        <?= strtoupper($item['status']) ?>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="form-field">
          <label class="text-muted-soft">Jenis Cuti</label>
          <div class="form-control-clean" style="background: var(--surface-2);"><?= e(ucfirst($item['jenis'])) ?></div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="form-field">
          <label class="text-muted-soft">Periode</label>
          <div class="form-control-clean" style="background: var(--surface-2);">
            <?= e(format_date_id($item['tanggal_mulai'])) ?>
            <?= $item['tanggal_mulai'] !== $item['tanggal_selesai'] ? ' — ' . e(format_date_id($item['tanggal_selesai'])) : '' ?>
          </div>
        </div>
      </div>
      <?php if (!empty($item['verifier_nama'])): ?>
        <div class="col-12 col-md-6">
          <div class="form-field">
            <label class="text-muted-soft">Diverifikasi oleh</label>
            <div class="form-control-clean" style="background: var(--surface-2);"><?= e($item['verifier_nama']) ?></div>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!empty($item['catatan'])): ?>
        <div class="col-12">
          <div class="form-field">
            <label class="text-muted-soft">Catatan</label>
            <div class="form-control-clean" style="background: var(--surface-2); white-space:pre-line;">
              <?= nl2br(e($item['catatan'])) ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
