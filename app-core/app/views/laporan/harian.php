<?php
$date = $date ?? date('Y-m-d');
?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-1">Laporan Harian</h2>
    <div class="text-muted-soft">Tanggal: <?= e($date) ?></div>
  </div>
  <form method="get" class="d-flex gap-2 flex-wrap">
    <input type="date" name="date" class="form-control form-control-sm" value="<?= e($date) ?>">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama atau NIY..." value="<?= e($q ?? '') ?>">
    <button class="btn btn-primary btn-sm">Tampilkan</button>
  </form>
</div>

<div class="card-soft p-0" style="overflow-x:auto">
  <table class="table table-hover align-middle mb-0">
    <thead style="background:var(--surface-2)">
      <tr>
        <th>#</th>
        <th>NIY</th><th>Nama</th><th>Role</th><th>Tanggal</th><th>Masuk</th><th>Menit Telat</th><th>Pulang</th><th class="text-center">Status</th><th class="text-end">Match</th><th class="text-end">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="11" class="text-center text-muted-soft py-4">Tidak ada data karyawan pada tanggal ini.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr data-edit-row="<?= (int)$r['user_id'] ?>">
          <td class="text-muted-soft text-center"><?= $i + 1 ?></td>
          <td class="text-muted-soft"><?= e($r['niy']) ?></td>
          <td class="fw-semibold"><?= e($r['nama']) ?></td>
          <td><?= e($r['role_name'] ?? '-') ?></td>
          <td><?= e($r['tanggal'] ?? $date) ?></td>
          <td>
            <input type="time" name="jam_masuk" form="edit-form-<?= (int)$r['user_id'] ?>" class="form-control form-control-sm edit-time-input bg-light text-muted" value="<?= e($r['jam_masuk'] ? date('H:i', strtotime($r['jam_masuk'])) : '') ?>" disabled>
          </td>
          <td class="text-center"><?= isset($r['terlambat_menit']) && $r['terlambat_menit']!==null ? (int)$r['terlambat_menit'] : '—' ?></td>
          <td>
            <input type="time" name="jam_keluar" form="edit-form-<?= (int)$r['user_id'] ?>" class="form-control form-control-sm edit-time-input bg-light text-muted" value="<?= e($r['jam_keluar'] ? date('H:i', strtotime($r['jam_keluar'])) : '') ?>" disabled>
          </td>
          <td class="text-center"><?= status_badge($r['status'] ?? 'belum_absen') ?></td>
          <td class="text-end text-muted-soft"><?= isset($r['face_match_masuk']) && $r['face_match_masuk']!==null ? number_format((float)$r['face_match_masuk'],3) : '—' ?></td>
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end align-items-center">
              <button type="button" class="btn btn-sm btn-light edit-row-btn" data-row-id="<?= (int)$r['user_id'] ?>" title="Edit kehadiran">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="submit" form="edit-form-<?= (int)$r['user_id'] ?>" class="btn btn-sm btn-primary save-row-btn" data-save-id="<?= (int)$r['user_id'] ?>" disabled title="Simpan perubahan">Simpan</button>
              <?php if (!empty($r['attendance_id'])): ?>
              <form method="post" action="<?= url('/laporan/harian/' . (int)$r['attendance_id'] . '/delete') ?>" class="d-inline form-confirm-delete">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus kehadiran"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
            <form id="edit-form-<?= (int)$r['user_id'] ?>" method="post" action="<?= url('/laporan/harian/user/' . (int)$r['user_id'] . '/edit') ?>" class="d-none">
              <?= csrf_field() ?>
              <input type="hidden" name="date" value="<?= e($date) ?>">
              <input type="hidden" name="attendance_id" value="<?= e((string)($r['attendance_id'] ?? '')) ?>">
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('tr[data-edit-row]');

    function setEditing(row, isEditing) {
      const inputs = row.querySelectorAll('.edit-time-input');
      const saveBtn = row.querySelector('.save-row-btn');
      inputs.forEach(function (input) {
        input.disabled = !isEditing;
        input.classList.toggle('bg-white', isEditing);
        input.classList.toggle('bg-light', !isEditing);
        input.classList.toggle('text-muted', !isEditing);
        input.classList.toggle('text-dark', isEditing);
      });
      if (saveBtn) saveBtn.disabled = !isEditing;
    }

    rows.forEach(function (row) {
      const btn = row.querySelector('.edit-row-btn');
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        rows.forEach(function (otherRow) {
          setEditing(otherRow, false);
        });
        setEditing(row, true);
        row.querySelector('.edit-time-input')?.focus();
      });
    });
  });
</script>
