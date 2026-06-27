<?php $errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('/karyawan') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
  <h2 class="mb-0">Edit Karyawan: <?= e($user['nama']) ?></h2>
</div>

<form method="post" action="<?= url('/karyawan/' . $user['id'] . '/edit') ?>" enctype="multipart/form-data" class="row g-3" id="form-karyawan">
  <?= csrf_field() ?>
  <input type="hidden" name="face_descriptor" id="face_descriptor">

  <div class="col-lg-8">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-person-badge"></i> Data Karyawan</h3>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">NIY <span class="text-danger">*</span></label>
          <input type="text" name="niy" value="<?= e($user['niy']) ?>" class="form-control <?= isset($errors['niy'])?'is-invalid':'' ?>" required>
          <div class="invalid-feedback"><?= e($errors['niy'] ?? '') ?></div>
        </div>
        <div class="col-md-8">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" value="<?= e($user['nama']) ?>" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Jabatan</label>
          <input type="text" name="jabatan" value="<?= e($user['jabatan']) ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $user['role_id']==$r['id']?'selected':'' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Shift</label>
          <div style="max-height: 140px; overflow-y: auto; border: 1px solid #eceff3; border-radius: 8px; padding: 8px; background: #f9fafb;">
            <?php foreach ($shifts as $s): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="shift_ids[]" id="shift_<?= $s['id'] ?>" value="<?= $s['id'] ?>" <?= in_array($s['id'], $shiftIds ?? []) ? 'checked' : '' ?>>
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
          <input type="email" name="email" value="<?= e($user['email']) ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">No. HP</label>
          <input type="text" name="phone" value="<?= e($user['phone']) ?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Jumlah Cuti</label>
          <input type="number" name="jumlah_cuti" value="<?= e($user['jumlah_cuti']) ?>" class="form-control" min="0" max="60">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12"><h5><i class="bi bi-geo-alt"></i> Geofence Kantor</h5></div>
        <div class="col-md-4"><label class="form-label">Latitude</label><input type="text" name="latitude_kantor" value="<?= e($user['latitude_kantor']) ?>" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Longitude</label><input type="text" name="longitude_kantor" value="<?= e($user['longitude_kantor']) ?>" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Radius (meter)</label><input type="number" name="radius_meter" value="<?= e($user['radius_meter']) ?>" class="form-control"></div>

        <div class="col-12"><hr></div>
        <div class="col-md-6">
          <label class="form-label">Reset Password (opsional)</label>
          <input type="text" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak diubah">
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check form-switch">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" <?= $user['is_active']?'checked':'' ?>>
            <label for="is_active" class="form-check-label">Akun Aktif</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-camera"></i> Foto Wajah</h3>

      <?php if (!empty($user['foto_profile'])): ?>
        <div class="mb-2">
          <small class="text-muted-soft">Foto saat ini:</small><br>
          <img src="<?= upload_url('profile/' . $user['foto_profile']) ?>" class="img-fluid rounded mb-2" style="max-height:160px">
        </div>
      <?php endif; ?>

      <p class="small text-muted-soft mb-2">
        Upload foto baru jika ingin <strong>memperbarui</strong> foto + face descriptor. Kosongkan jika tidak ada perubahan.
        <?= !empty($user['face_descriptor']) ? '<br><span class="text-success"><i class="bi bi-check-circle-fill"></i> Face descriptor lama tersimpan.</span>' : '<br><span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Belum ada face descriptor.</span>' ?>
      </p>

      <input type="file" name="foto_profile" id="foto_profile" accept="image/*" class="form-control mb-3">

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
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
  </div>
</form>

<script src="<?= asset('vendor/face-api/face-api.min.js') ?>"></script>
<script src="<?= asset('js/face-capture.js') ?>"></script>
<script>FaceCapture.init({ modelsUrl: '<?= asset('models') ?>', requireDescriptor: false });</script>
