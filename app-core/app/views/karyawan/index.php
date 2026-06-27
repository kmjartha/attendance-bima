<?php $errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="mb-0">Master Karyawan</h2>
    <small class="text-muted-soft">Kelola data semua karyawan sekolah, termasuk foto wajah untuk facial recognition.</small>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <form method="get" class="d-flex gap-2" style="flex: 1; min-width: 250px;">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama atau NIY..." value="<?= e($q ?? '') ?>">
      <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-search"></i></button>
    </form>
    <?php if (!has_role('Supervisor')): ?>
      <a href="<?= url('/karyawan/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Karyawan
      </a>
    <?php else: ?>
      <div class="text-muted-soft">Supervisor hanya dapat melihat daftar dan detail karyawan.</div>
    <?php endif; ?>
  </div>
</div>

<div class="card-soft p-0">
  <div class="table-responsive">
    <table id="tbl-karyawan" class="table align-middle mb-0" style="width:100%">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Foto</th>
          <th>NIY</th>
          <th>Nama</th>
          <th>Jabatan</th>
          <th>Role</th>
          <th>Status</th>
          <th>Wajah</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $i => $u): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td>
            <?php if (!empty($u['foto_profile'])): ?>
              <img src="<?= upload_url('profile/' . $u['foto_profile']) ?>" class="avatar-sm" alt="">
            <?php else: ?>
              <div class="avatar-sm avatar-fallback"><?= e(initials($u['nama'])) ?></div>
            <?php endif; ?>
          </td>
          <td><code><?= e($u['niy']) ?></code></td>
          <td>
            <div class="fw-semibold"><?= e($u['nama']) ?></div>
            <div class="small text-muted-soft"><?= e($u['email'] ?? '') ?></div>
          </td>
          <td><?= e($u['jabatan'] ?? '-') ?></td>
          <td><span class="badge bg-primary-subtle text-primary"><?= e($u['role_name']) ?></span></td>
          <td>
            <?= $u['is_active']
                ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Non-aktif</span>' ?>
          </td>
          <td>
            <?= !empty($u['face_descriptor'])
                ? '<i class="bi bi-check-circle-fill text-success" title="Face descriptor tersedia"></i>'
                : '<i class="bi bi-exclamation-circle text-warning" title="Belum ada"></i>' ?>
          </td>
          <td class="text-end">
            <a href="<?= url('/karyawan/' . $u['id']) ?>" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
            <?php if (!has_role('Supervisor')): ?>
              <a href="<?= url('/karyawan/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/karyawan/' . $u['id'] . '/delete') ?>" class="d-inline form-confirm-delete">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<link rel="stylesheet" href="<?= asset('vendor/datatables/datatables.min.css') ?>">
<script src="<?= asset('vendor/datatables/datatables.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  new DataTable('#tbl-karyawan', {
    pageLength: 10,
    order: [[3,'asc']],
    columnDefs: [{ orderable:false, targets:[0,1,7,8] }],
    language: { search: 'Cari:', lengthMenu: 'Tampil _MENU_', info: 'Menampilkan _START_–_END_ dari _TOTAL_', paginate:{previous:'‹', next:'›'} }
  });
});
</script>
