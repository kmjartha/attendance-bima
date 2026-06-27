<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('/pengumuman') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
  <h2 class="mb-0">Tambah Pengumuman</h2>
</div>

<form method="post" action="<?= url('/pengumuman/create') ?>" enctype="multipart/form-data" class="card-soft">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Judul</label>
    <input type="text" name="judul" class="form-control" maxlength="180" required value="<?= e(old('judul')) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Isi Pengumuman</label>
    <textarea name="isi" class="form-control" rows="8" required><?= e(old('isi')) ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Gambar (opsional)</label>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
    <div class="form-text">Unggah gambar untuk membuat notifikasi dan halaman detail lebih menarik.</div>
  </div>
  <div class="mb-3 form-check form-switch">
    <input type="checkbox" name="is_published" id="pub" class="form-check-input" checked>
    <label for="pub" class="form-check-label">Langsung publikasikan</label>
  </div>
  <div class="text-end">
    <a href="<?= url('/pengumuman') ?>" class="btn btn-light">Batal</a>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
  </div>
</form>
