<?php $selectedYears = array_unique(array_merge($years, [(int)date('Y'), (int)date('Y') + 1])); rsort($selectedYears); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h2 class="mb-0">Kalender Hari Libur</h2>
    <small class="text-muted-soft">Dipakai sistem untuk mengecualikan role tertentu dari status alpha di hari libur (lihat menu Kebijakan Absensi).</small>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <form method="get">
      <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($selectedYears as $y): ?>
          <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-libur-create">
      <i class="bi bi-plus-lg"></i> Tambah Hari Libur
    </button>
  </div>
</div>

<div class="card-soft p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Tanggal</th>
          <th>Keterangan</th>
          <th>Tipe</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($holidays)): ?>
        <tr><td colspan="4" class="text-center text-muted-soft py-4">Belum ada data hari libur untuk tahun <?= $year ?>.</td></tr>
      <?php endif; ?>
      <?php foreach ($holidays as $h): ?>
        <tr>
          <td class="fw-semibold"><?= format_date_id($h['tanggal']) ?></td>
          <td><?= e($h['keterangan']) ?></td>
          <td><?= holiday_type_badge($h['tipe']) ?></td>
          <td class="text-end">
            <form method="post" action="<?= url('/pengaturan/libur/' . $h['id'] . '/delete') ?>" class="form-confirm-delete d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Hari Libur -->
<div class="modal fade" id="modal-libur-create" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= url('/pengaturan/libur/create') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Tambah Hari Libur</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <input type="text" name="keterangan" class="form-control" placeholder="mis. Hari Raya Galungan" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Tipe</label>
          <select name="tipe" class="form-select">
            <option value="nasional">Nasional</option>
            <option value="cuti_bersama">Cuti Bersama</option>
            <option value="sekolah">Libur Sekolah</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
