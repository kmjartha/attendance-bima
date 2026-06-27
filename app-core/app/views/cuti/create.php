<?php $errors = $_SESSION['_errors'] ?? []; ?>
<div class="page-head mb-3">
  <h2 class="mb-1">Ajukan Cuti / Sakit</h2>
  <div class="text-muted-soft">Sisa cuti tahunan: <strong><?= (int)$me['jumlah_cuti'] ?> hari</strong></div>
</div>

<form method="post" enctype="multipart/form-data" class="card-soft" style="max-width:680px">
  <?= csrf_field() ?>

  <label class="form-label fw-semibold">Jenis Cuti</label>
  <select name="jenis" class="form-select mb-3 <?= isset($errors['jenis'])?'is-invalid':'' ?>" required>
    <option value="">— Pilih —</option>
    <?php foreach (['tahunan'=>'Cuti Tahunan','sakit'=>'Sakit','melahirkan'=>'Melahirkan','menikah'=>'Menikah'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= old('jenis')===$k?'selected':'' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>

  <div class="row g-2 mb-3">
    <div class="col-6">
      <label class="form-label fw-semibold">Tanggal Mulai</label>
      <input type="date" name="tanggal_mulai" class="form-control <?= isset($errors['tanggal_mulai'])?'is-invalid':'' ?>" value="<?= e(old('tanggal_mulai')) ?>" required>
    </div>
    <div class="col-6">
      <label class="form-label fw-semibold">Tanggal Selesai</label>
      <input type="date" name="tanggal_selesai" class="form-control <?= isset($errors['tanggal_selesai'])?'is-invalid':'' ?>" value="<?= e(old('tanggal_selesai')) ?>" required>
      <?php if(isset($errors['tanggal_selesai'])): ?><div class="invalid-feedback d-block"><?= e($errors['tanggal_selesai']) ?></div><?php endif; ?>
    </div>
  </div>

  <label class="form-label fw-semibold">Alasan</label>
  <textarea name="alasan" rows="4" class="form-control mb-3 <?= isset($errors['alasan'])?'is-invalid':'' ?>" required maxlength="1000" placeholder="Tuliskan alasan cuti / sakit Anda…"><?= e(old('alasan')) ?></textarea>

  <label class="form-label fw-semibold">Surat / Lampiran <span class="text-muted-soft">(wajib utk Sakit · PDF/JPG/PNG max 5 MB)</span></label>
  <input type="file" name="file_surat" class="form-control mb-3" accept="application/pdf,image/jpeg,image/png">

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/cuti') ?>" class="btn btn-light">Batal</a>
    <button class="btn btn-primary"><i class="bi bi-send-fill"></i> Kirim Pengajuan</button>
  </div>
</form>
<?php unset($_SESSION['_old'], $_SESSION['_errors']); ?>
