<?php $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-1">Laporan Karyawan</h2>
    <div class="text-muted-soft">Periode <?= e(format_date_id($startDate)) ?> - <?= e(format_date_id($endDate)) ?> · <?= count($rows) ?> karyawan</div>
  </div>
</div>

<form method="get" class="card-soft p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
  <div>
    <label class="form-label small mb-1">Dari</label>
    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
  </div>
  <div>
    <label class="form-label small mb-1">Sampai</label>
    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
  </div>
  <div>
    <label class="form-label small mb-1">Role</label>
    <select name="role" class="form-select form-select-sm">
      <option value="">Semua</option>
      <?php foreach (['Guru','Staff','Security','Kepsek','HRD'] as $r): ?>
        <option value="<?= $r ?>" <?= $role===$r?'selected':'' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex-grow-1" style="min-width:180px">
    <label class="form-label small mb-1">Cari NIY/Nama</label>
    <input type="text" name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Cari…">
  </div>
  <button class="btn btn-primary btn-sm">Tampilkan</button>
  <a href="<?= url('/laporan/export') ?>?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?><?= $q !== '' ? '&q='.urlencode($q) : '' ?><?= $role !== '' ? '&role='.urlencode($role) : '' ?>" class="btn btn-success btn-sm">
    <i class="bi bi-file-earmark-excel-fill"></i> Excel
  </a>
</form>

<div class="card-soft p-0" style="overflow-x:auto">
  <table class="table table-hover align-middle mb-0">
    <thead style="background:var(--surface-2)">
      <tr>
        <th>NIY</th><th>Nama</th><th>Role</th>
        <th class="text-center">Hadir</th>
        <th class="text-center">Telat</th>
        <th class="text-center">Menit Telat</th>
        <th class="text-center">Izin</th>
        <th class="text-center">Sakit</th>
        <th class="text-center">Alpha</th>
        <th class="text-center">Total Hadir</th>
        <th class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="11" class="text-center text-muted-soft py-4">Tidak ada karyawan sesuai filter.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-muted-soft"><?= e($r['niy']) ?></td>
          <td class="fw-semibold"><?= e($r['nama']) ?></td>
          <td><span class="badge bg-light text-dark"><?= e($r['role_name']) ?></span></td>
          <td class="text-center text-success fw-semibold"><?= (int)$r['hadir'] ?></td>
          <td class="text-center text-warning fw-semibold"><?= (int)$r['telat'] ?></td>
          <td class="text-center text-warning fw-semibold"><?= (int)$r['menit_telat'] ?></td>
          <td class="text-center"><?= (int)$r['izin'] ?></td>
          <td class="text-center text-danger"><?= (int)$r['sakit'] ?></td>
          <td class="text-center text-secondary"><?= (int)$r['alpha'] ?></td>
          <td class="text-center fw-bold"><?= (int)$r['total_hadir'] ?></td>
          <td class="text-center">
            <a class="btn btn-sm btn-outline-primary"
               href="<?= url("/laporan/karyawan/{$r['id']}?start_date={$startDate}&end_date={$endDate}") ?>">
              <i class="bi bi-eye"></i> Detail
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
