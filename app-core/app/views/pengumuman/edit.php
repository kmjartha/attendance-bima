<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('/pengumuman') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
  <h2 class="mb-0">Edit Pengumuman</h2>
</div>

<form method="post" action="<?= url('/pengumuman/' . $item['id'] . '/edit') ?>" enctype="multipart/form-data" class="card-soft">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Judul</label>
    <input type="text" name="judul" class="form-control" maxlength="180" required value="<?= e($item['judul']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Isi Pengumuman</label>
    <textarea name="isi" class="form-control" rows="8" required><?= e($item['isi']) ?></textarea>
  </div>
  <?php if (!empty($item['image'])): ?>
    <div class="mb-3">
      <label class="form-label">Gambar saat ini</label>
      <div class="mb-2">
        <img src="<?= upload_url('announcements/' . $item['image']) ?>" alt="Gambar pengumuman" class="img-fluid rounded" style="max-height:180px; object-fit:cover; width:auto;">
      </div>
    </div>
  <?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Gambar baru (opsional)</label>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
    <div class="form-text">Unggah gambar baru jika ingin mengganti gambar pengumuman.</div>
  </div>
  <div class="mb-3 form-check form-switch">
    <input type="checkbox" name="is_published" id="pub" class="form-check-input" <?= $item['is_published']?'checked':'' ?>>
    <label for="pub" class="form-check-label">Dipublikasikan</label>
  </div>
  <div class="text-end">
    <a href="<?= url('/pengumuman') ?>" class="btn btn-light">Batal</a>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
  </div>
</form>
