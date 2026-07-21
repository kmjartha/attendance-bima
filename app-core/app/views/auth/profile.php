<div class="page-head d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
  <div class="d-flex align-items-center gap-2">
    <a href="<?= url('/dashboard') ?>" class="btn btn-light btn-sm d-md-none">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="mb-1">Profil Saya</h1>
      <p class="text-muted mb-0">Lihat detail akun Anda dan kelola password dengan aman.</p>
    </div>
  </div>
</div>

<div class="card-soft mb-4">
  <div class="card-body">
    <div class="row gy-4">
      <div class="col-md-4 text-center">
        <?php if (!empty($user['foto_profile'])): ?>
          <img src="<?= upload_url('profile/' . $user['foto_profile']) ?>" class="img-fluid rounded-circle mb-3" style="max-width:180px;" alt="Foto profil">
        <?php else: ?>
          <div class="avatar avatar-xl bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center fs-1 mb-3" style="width:180px;height:180px;">
            <?= e(initials($user['nama'] ?? '?')) ?>
          </div>
        <?php endif; ?>

        <div class="fw-semibold fs-5"><?= e($user['nama'] ?? '-') ?></div>
        <div class="text-muted"><?= e($user['role_name'] ?? '-') ?></div>
      </div>

      <div class="col-md-8">
        <div class="row mb-3">
          <div class="col-sm-4 text-muted">NIY</div>
          <div class="col-sm-8 fw-semibold"><?= e($user['niy'] ?? '-') ?></div>
        </div>
        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Jabatan</div>
          <div class="col-sm-8 fw-semibold"><?= e($user['jabatan'] ?? '-') ?></div>
        </div>
        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Email</div>
          <div class="col-sm-8 fw-semibold"><?= e($user['email'] ?? '-') ?></div>
        </div>
        <div class="row mb-0">
          <div class="col-sm-4 text-muted">Telepon</div>
          <div class="col-sm-8 fw-semibold"><?= e($user['phone'] ?? '-') ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft h-100">
      <div class="card-body">
        <h2 class="card-title">Ubah Password</h2>
        <p class="text-muted">Pastikan password baru Anda kuat dan mudah diingat.</p>

        <form method="post" action="<?= url('/profile/password') ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="current_password" class="form-label">Password saat ini</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label">Password baru</label>
            <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
            <div class="form-text">Minimal 6 karakter.</div>
          </div>

          <div class="mb-4">
            <label for="confirm_password" class="form-label">Konfirmasi password baru</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary"><i class="bi bi-key-fill me-1"></i> Simpan Password</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft h-100">
      <div class="card-body d-flex flex-column justify-content-between">
        <div>
          <p class="text-muted mb-3">Gunakan tombol di bawah untuk keluar.</p>
        </div>
        <a href="<?= url('/logout') ?>" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
      </div>
    </div>
  </div>
</div>
