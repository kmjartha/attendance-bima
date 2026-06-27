<div class="page-head d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-1">Cuti / Sakit</h2>
    <div class="text-muted-soft">Sisa cuti tahunan Anda: <strong><?= (int)$me['jumlah_cuti'] ?> hari</strong></div>
  </div>
  <?php if (!has_role('Supervisor')): ?>
    <a href="<?= url('/cuti/create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Ajukan Baru</a>
  <?php else: ?>
    <div class="text-muted-soft">Supervisor hanya dapat melihat riwayat cuti dan tidak dapat menambahkan pengajuan baru.</div>
  <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
  <div class="card-soft text-center" style="padding:2rem">
    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--text-soft)"></i>
    <h3 class="mt-2 mb-1" style="font-size:1rem">Belum ada pengajuan</h3>
    <p class="text-muted-soft mb-3">Tekan tombol di atas untuk mengajukan cuti pertama Anda.</p>
  </div>
<?php else: ?>
  <div class="card-soft p-0">
    <?php foreach ($rows as $r):
      $jenisColor = ['sakit'=>'danger','tahunan'=>'primary','melahirkan'=>'info','menikah'=>'warning'][$r['jenis']] ?? 'secondary';
    ?>
      <div class="cuti-row">
        <div class="c-icon bg-<?= $jenisColor ?>-subtle text-<?= $jenisColor ?>">
          <i class="bi bi-<?= $r['jenis']==='sakit'?'bandaid-fill':($r['jenis']==='melahirkan'?'heart-fill':($r['jenis']==='menikah'?'gift-fill':'calendar-event-fill')) ?>"></i>
        </div>
        <div class="c-body">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <strong class="text-capitalize"><?= e($r['jenis']) ?></strong>
            <?= status_badge($r['status']) ?>
          </div>
          <div class="text-muted-soft" style="font-size:.82rem">
            <?= e(format_date_id($r['tanggal_mulai'])) ?> — <?= e(format_date_id($r['tanggal_selesai'])) ?>
          </div>
          <div class="mt-1" style="font-size:.85rem"><?= e($r['alasan']) ?></div>
          <?php if ($r['file_surat']): ?>
            <a class="d-inline-block mt-1" style="font-size:.78rem"
               href="<?= e(upload_url($r['file_surat'])) ?>" target="_blank">
              <i class="bi bi-paperclip"></i> Lihat lampiran
            </a>
          <?php endif; ?>
          <?php if ($r['catatan']): ?>
            <div class="alert-soft mt-2"><i class="bi bi-chat-left-text"></i> <strong>Catatan:</strong> <?= e($r['catatan']) ?>
              <?php if ($r['verifier_nama']): ?>
                <span class="text-muted-soft">— <?= e($r['verifier_nama']) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
