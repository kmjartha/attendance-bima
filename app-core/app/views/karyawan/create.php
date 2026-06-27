<?php $errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('/karyawan') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
  <h2 class="mb-0">Tambah Karyawan Baru</h2>
</div>

<form method="post" action="<?= url('/karyawan/create') ?>" enctype="multipart/form-data" class="row g-3" id="form-karyawan">
  <?= csrf_field() ?>
  <input type="hidden" name="face_descriptor" id="face_descriptor">

  <div class="col-lg-8">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-person-badge"></i> Data Karyawan</h3>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">NIY <span class="text-danger">*</span></label>
          <input type="text" name="niy" value="<?= e(old('niy')) ?>" class="form-control <?= isset($errors['niy'])?'is-invalid':'' ?>" required>
          <div class="invalid-feedback"><?= e($errors['niy'] ?? '') ?></div>
        </div>
        <div class="col-md-8">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" value="<?= e(old('nama')) ?>" class="form-control <?= isset($errors['nama'])?'is-invalid':'' ?>" required>
          <div class="invalid-feedback"><?= e($errors['nama'] ?? '') ?></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Jabatan</label>
          <input type="text" name="jabatan" value="<?= e(old('jabatan')) ?>" class="form-control" placeholder="Guru Matematika, Staff TU, ...">
        </div>
        <div class="col-md-3">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select name="role_id" class="form-select" required>
            <option value="">— pilih —</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= old('role_id')==$r['id']?'selected':'' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Shift</label>
          <div style="max-height: 140px; overflow-y: auto; border: 1px solid #eceff3; border-radius: 8px; padding: 8px; background: #f9fafb;">
            <?php foreach ($shifts as $s): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="shift_ids[]" id="shift_<?= $s['id'] ?>" value="<?= $s['id'] ?>" <?= in_array($s['id'], (array)old('shift_ids', [])) ? 'checked' : '' ?>>
                <label class="form-check-label" for="shift_<?= $s['id'] ?>">
                  <?= e($s['nama']) ?> <span class="text-muted small">(<?= substr($s['jam_masuk'],0,5) ?>–<?= substr($s['jam_keluar'],0,5) ?>)</span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <small class="text-muted">Pilih satu atau lebih shift. Pilihan pertama jadi default.</small>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" value="<?= e(old('email')) ?>" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>">
          <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">No. HP</label>
          <input type="text" name="phone" value="<?= e(old('phone')) ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Jumlah Cuti</label>
          <input type="number" name="jumlah_cuti" value="<?= e(old('jumlah_cuti', 12)) ?>" class="form-control" min="0" max="60">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12"><h5><i class="bi bi-geo-alt"></i> Geofence Kantor</h5></div>
        <div class="col-md-4">
          <label class="form-label">Latitude</label>
          <input type="text" name="latitude_kantor" value="<?= e(old('latitude_kantor', '-8.78560562700156')) ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Longitude</label>
          <input type="text" name="longitude_kantor" value="<?= e(old('longitude_kantor', '115.17669738117321')) ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Radius (Meter)</label>
          <input type="number" name="radius_meter" value="<?= e(old('radius_meter', 180)) ?>" class="form-control">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-md-6">
          <label class="form-label">Password Awal <span class="text-danger">*</span></label>
          <input type="text" name="password" class="form-control <?= isset($errors['password'])?'is-invalid':'' ?>" minlength="6" required>
          <div class="form-text">Minimal 6 karakter. Karyawan dapat ganti password setelah login.</div>
          <div class="invalid-feedback"><?= e($errors['password'] ?? '') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-camera"></i> Foto Wajah (Face Recognition)</h3>
      <p class="small text-muted-soft mb-3">
        Upload foto potrait jelas (1 wajah, pencahayaan baik). Sistem akan mengekstrak <em>face descriptor</em> 128 dimensi untuk dipakai saat absensi.
      </p>

      <input type="file" name="foto_profile" id="foto_profile" accept="image/*" class="form-control mb-3" required>

      <div class="face-preview-wrap">
        <img id="face-preview" alt="" />
        <canvas id="face-overlay"></canvas>
      </div>

      <div id="face-status" class="alert alert-secondary mt-3 mb-0 small d-none"></div>

      <button type="button" id="btn-extract" class="btn btn-outline-primary w-100 mt-3" disabled>
        <i class="bi bi-magic"></i> Ekstrak Face Descriptor
      </button>
    </div>
  </div>

  <div class="col-12 d-flex justify-content-end gap-2 mt-2">
    <a href="<?= url('/karyawan') ?>" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary" id="btn-submit"><i class="bi bi-check-lg"></i> Simpan Karyawan</button>
  </div>
</form>

<script src="<?= asset('vendor/face-api/face-api.min.js') ?>"></script>
<script src="<?= asset('js/face-capture.js') ?>"></script>
<script>FaceCapture.init({ modelsUrl: '<?= asset('models') ?>', requireDescriptor: true });</script>
