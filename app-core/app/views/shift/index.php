<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="mb-0">Master Shift Kerja</h2>
    <small class="text-muted-soft">Atur jam masuk/keluar, toleransi keterlambatan, dan tanggal cut-off bulanan.</small>
  </div>
  <?php if (has_role('HRD')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-shift-create">
      <i class="bi bi-plus-lg"></i> Tambah Shift
    </button>
  <?php else: ?>
    <div class="text-muted-soft">Supervisor hanya dapat melihat daftar shift.</div>
  <?php endif; ?>
</div>

<div class="row g-3">
<?php foreach ($shifts as $s): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-soft h-100">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <h4 class="mb-0"><?= e($s['nama']) ?></h4>
          <span class="text-muted-soft small">Cut-off setiap tgl <?= (int)$s['cut_off_tanggal'] ?></span>
        </div>
        <?= $s['is_active']
            ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Non-aktif</span>' ?>
      </div>

      <div class="shift-time mb-3">
        <div><i class="bi bi-sunrise text-warning"></i> Masuk <strong><?= substr($s['jam_masuk'],0,5) ?></strong></div>
        <div><i class="bi bi-sunset text-primary"></i> Keluar <strong><?= substr($s['jam_keluar'],0,5) ?></strong></div>
        <div><i class="bi bi-stopwatch text-danger"></i> Toleransi <strong><?= (int)$s['toleransi_menit'] ?> mnt</strong></div>
      </div>

      <?php if (has_role('HRD')): ?>
        <div class="d-flex gap-2">
          <button class="btn btn-light flex-grow-1 btn-edit-shift"
                  data-id="<?= $s['id'] ?>"
                  data-nama="<?= e($s['nama']) ?>"
                  data-masuk="<?= substr($s['jam_masuk'],0,5) ?>"
                  data-keluar="<?= substr($s['jam_keluar'],0,5) ?>"
                  data-tol="<?= (int)$s['toleransi_menit'] ?>"
                  data-cut="<?= (int)$s['cut_off_tanggal'] ?>"
                  data-active="<?= (int)$s['is_active'] ?>">
            <i class="bi bi-pencil"></i> Edit
          </button>
          <form method="post" action="<?= url('/shift/' . $s['id'] . '/delete') ?>" class="form-confirm-delete">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-light text-danger"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- Modal Create -->
<div class="modal fade" id="modal-shift-create" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= url('/shift/create') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Tambah Shift</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php include APP_PATH . '/views/shift/_form.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modal-shift-edit" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="" id="form-edit-shift" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Edit Shift</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php $isEdit = true; include APP_PATH . '/views/shift/_form.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = new bootstrap.Modal('#modal-shift-edit');
  const form  = document.getElementById('form-edit-shift');
  document.querySelectorAll('.btn-edit-shift').forEach(btn => {
    btn.addEventListener('click', () => {
      form.action = '<?= url('/shift') ?>/' + btn.dataset.id + '/edit';
      form.querySelector('[name=nama]').value = btn.dataset.nama;
      form.querySelector('[name=jam_masuk]').value = btn.dataset.masuk;
      form.querySelector('[name=jam_keluar]').value = btn.dataset.keluar;
      form.querySelector('[name=toleransi_menit]').value = btn.dataset.tol;
      form.querySelector('[name=cut_off_tanggal]').value = btn.dataset.cut;
      form.querySelector('[name=is_active]').checked = btn.dataset.active === '1';
      modal.show();
    });
  });
});
</script>
