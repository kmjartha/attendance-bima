<div class="page-head d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="mb-1">Verifikasi Cuti</h2>
    <div class="text-muted-soft">
      <?php if (has_role('HRD')): ?>
        Semua pengajuan dari seluruh karyawan
      <?php elseif (has_role('Supervisor')): ?>
        Supervisor hanya melihat pengajuan Manajerial
      <?php else: ?>
        Pengajuan dari karyawan non-HRD
      <?php endif; ?>
    </div>
  </div>
  <div class="btn-group">
    <a href="<?= url('/verifikasi-cuti') ?>" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-primary' ?>">Semua</a>
    <a href="<?= url('/verifikasi-cuti?status=pending') ?>" class="btn btn-sm <?= $status==='pending'?'btn-primary':'btn-outline-primary' ?>">Pending</a>
    <a href="<?= url('/verifikasi-cuti?status=approved') ?>" class="btn btn-sm <?= $status==='approved'?'btn-primary':'btn-outline-primary' ?>">Disetujui</a>
    <a href="<?= url('/verifikasi-cuti?status=rejected') ?>" class="btn btn-sm <?= $status==='rejected'?'btn-primary':'btn-outline-primary' ?>">Ditolak</a>
  </div>
</div>

<?php if (empty($rows)): ?>
  <div class="card-soft text-center" style="padding:2rem">
    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--text-soft)"></i>
    <h3 class="mt-2 mb-1" style="font-size:1rem">Tidak ada pengajuan pada filter ini</h3>
  </div>
<?php else: ?>
  <div class="card-soft p-0" style="overflow-x:auto">
    <table class="table align-middle mb-0">
      <thead style="background:var(--surface-2)">
        <tr>
          <th>Pengaju</th><th>Jenis</th><th>Periode</th><th>Alasan</th>
          <th>Status</th><th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($r['user_nama']) ?></div>
              <div class="text-muted-soft" style="font-size:.78rem"><?= e($r['user_niy']) ?> · <?= e($r['user_role']) ?></div>
            </td>
            <td><span class="badge bg-light text-dark text-capitalize"><?= e($r['jenis']) ?></span></td>
            <td>
              <?= e(format_date_id($r['tanggal_mulai'])) ?><br>
              <span class="text-muted-soft" style="font-size:.78rem">s/d <?= e(format_date_id($r['tanggal_selesai'])) ?></span>
            </td>
            <td style="max-width:280px"><?= e($r['alasan']) ?>
              <?php if ($r['file_surat']): ?>
                <a class="d-block mt-1" style="font-size:.78rem" href="<?= e(upload_url($r['file_surat'])) ?>" target="_blank">
                  <i class="bi bi-paperclip"></i> Lampiran
                </a>
              <?php endif; ?>
            </td>
            <td>
              <?= status_badge($r['status']) ?>
              <?php if ($r['catatan']): ?>
                <div class="text-muted-soft mt-1" style="font-size:.72rem"><?= e($r['catatan']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($r['status'] === 'pending'): ?>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#mApprove<?= $r['id'] ?>">
                  <i class="bi bi-check-lg"></i> Setujui
                </button>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#mReject<?= $r['id'] ?>">
                  <i class="bi bi-x-lg"></i> Tolak
                </button>
                <?php foreach (['approve'=>['success','Setujui Pengajuan'],'reject'=>['danger','Tolak Pengajuan']] as $aksi=>$meta): ?>
                  <div class="modal fade" id="m<?= ucfirst($aksi) ?><?= $r['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <form method="post" action="<?= url('/verifikasi-cuti/'.$r['id'].'/action') ?>" class="modal-content">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="<?= $aksi ?>">
                        <div class="modal-header"><h5 class="modal-title"><?= $meta[1] ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                          <p>Pengaju: <strong><?= e($r['user_nama']) ?></strong> · Jenis: <strong class="text-capitalize"><?= e($r['jenis']) ?></strong></p>
                          <label class="form-label">Catatan <span class="text-muted-soft">(opsional)</span></label>
                          <textarea name="catatan" rows="3" maxlength="500" class="form-control" placeholder="Catatan untuk pengaju…"></textarea>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                          <button class="btn btn-<?= $meta[0] ?>"><?= $meta[1] ?></button>
                        </div>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-muted-soft" style="font-size:.78rem">
                  <?= $r['verifier_nama'] ? 'oleh '.e($r['verifier_nama']) : '' ?>
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
