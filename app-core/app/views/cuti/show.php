<div class="page-head mb-3">
  <h2 class="mb-1">Detail Cuti</h2>
  <?php if ($row['user_nama'] ?? null): ?>
    <div class="text-muted-soft"><?= e($row['user_nama']) ?> — <?= e($row['user_role'] ?? '') ?></div>
  <?php endif; ?>
</div>

<div class="card-soft" style="max-width:680px">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <div class="text-muted-soft" style="font-size:.78rem">Jenis Cuti</div>
      <div class="fw-semibold text-capitalize"><?= e($row['jenis']) ?></div>
    </div>
    <div><?= status_badge($row['status']) ?></div>
  </div>

  <div class="mb-3">
    <div class="text-muted-soft mb-2" style="font-size:.78rem">Tanggal</div>
    <?php if (empty($dates)): ?>
      <div class="text-muted-soft"><?= e(format_date_id($row['tanggal_mulai'])) ?><?= $row['tanggal_selesai'] !== $row['tanggal_mulai'] ? ' — ' . e(format_date_id($row['tanggal_selesai'])) : '' ?></div>
    <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($dates as $d): ?>
          <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
            <span><?= e(format_date_id($d['tanggal'])) ?></span>
            <?php if (!empty($d['keterangan'])): ?>
              <span class="text-muted-soft text-end" style="font-size:.82rem;max-width:60%"><?= e($d['keterangan']) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <div class="text-muted-soft mb-1" style="font-size:.78rem">Alasan</div>
    <div><?= nl2br(e($row['alasan'])) ?></div>
  </div>

  <?php if (!empty($row['file_surat'])): ?>
    <div class="mb-3">
      <div class="text-muted-soft mb-1" style="font-size:.78rem">Lampiran</div>
      <a href="<?= e(upload_url($row['file_surat'])) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-paperclip"></i> Lihat Lampiran
      </a>
    </div>
  <?php endif; ?>

  <?php if ($row['status'] !== 'pending'): ?>
    <div class="mb-3">
      <div class="text-muted-soft mb-1" style="font-size:.78rem">Diproses oleh</div>
      <div><?= $row['verifier_nama'] ? e($row['verifier_nama']) : '-' ?></div>
      <?php if (!empty($row['catatan'])): ?>
        <div class="text-muted-soft mt-1" style="font-size:.82rem"><?= e($row['catatan']) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-end">
    <a href="<?= url(is_pegawai() ? '/cuti' : '/verifikasi-cuti') ?>" class="btn btn-light">Kembali</a>
  </div>
</div>
