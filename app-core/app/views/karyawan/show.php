<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('/karyawan') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
  <h2 class="mb-0">Detail Karyawan</h2>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card-soft text-center">
      <?php if (!empty($user['foto_profile'])): ?>
        <img src="<?= upload_url('profile/' . $user['foto_profile']) ?>" class="img-fluid rounded mb-3" style="max-height:240px">
      <?php else: ?>
        <div class="avatar-xl mx-auto mb-3"><?= e(initials($user['nama'])) ?></div>
      <?php endif; ?>
      <h4 class="mb-0"><?= e($user['nama']) ?></h4>
      <div class="text-muted-soft mb-2"><?= e($user['jabatan'] ?? '-') ?></div>
      <span class="badge bg-primary-subtle text-primary"><?= e($user['role_name']) ?></span>
      <?= $user['is_active'] ? '<span class="badge bg-success-subtle text-success ms-1">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary ms-1">Non-aktif</span>' ?>

      <hr>
      <div class="text-start small">
        <div><strong><?= !empty($user['face_descriptor'])?'<i class="bi bi-check-circle-fill text-success"></i>':'<i class="bi bi-exclamation-circle text-warning"></i>' ?> Face Descriptor</strong>
          <span class="float-end"><?= !empty($user['face_descriptor']) ? 'Tersimpan' : 'Belum ada' ?></span>
        </div>
      </div>
      <a href="<?= url('/karyawan/' . $user['id'] . '/edit') ?>" class="btn btn-primary w-100 mt-3">
        <i class="bi bi-pencil"></i> Edit
      </a>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-info-circle"></i> Informasi</h3>
      <dl class="row mb-0">
        <dt class="col-sm-4">NIY</dt>            <dd class="col-sm-8"><code><?= e($user['niy']) ?></code></dd>
        <dt class="col-sm-4">Email</dt>          <dd class="col-sm-8"><?= e($user['email'] ?? '-') ?></dd>
        <dt class="col-sm-4">No. HP</dt>         <dd class="col-sm-8"><?= e($user['phone'] ?? '-') ?></dd>
        <dt class="col-sm-4">Jumlah Cuti</dt>    <dd class="col-sm-8"><?= (int)$user['jumlah_cuti'] ?> hari/tahun</dd>
        <dt class="col-sm-4">Shift</dt>          <dd class="col-sm-8">
          <?php if (!empty($shifts)): ?>
            <?php foreach ($shifts as $sh): ?>
              <span class="badge bg-primary-subtle text-primary me-1">
                <?= e($sh['nama']) ?> (<?= substr($sh['jam_masuk'],0,5) ?>–<?= substr($sh['jam_keluar'],0,5) ?>)
                <?= !empty($sh['is_default']) ? '· default' : '' ?>
              </span>
            <?php endforeach; ?>
          <?php else: ?><em>belum diatur</em><?php endif; ?>
        </dd>
        <dt class="col-sm-4">Lokasi Kantor</dt>  <dd class="col-sm-8"><?= e($user['latitude_kantor']) ?>, <?= e($user['longitude_kantor']) ?> &mdash; radius <?= (int)$user['radius_meter'] ?> m</dd>
        <dt class="col-sm-4">Terdaftar</dt>      <dd class="col-sm-8"><?= e(format_date_id($user['created_at'], true)) ?></dd>
      </dl>
    </div>
  </div>
</div>
