<?php $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-1">Laporan Rekap Absensi</h2>
    <div class="text-muted-soft">Periode <?= e($bulan[$month]) ?> <?= (int)$year ?> · <?= count($rows) ?> karyawan</div>
  </div>
  <form method="get" class="d-flex gap-2">
    <select name="month" class="form-select form-select-sm">
      <?php for ($i=1;$i<=12;$i++): ?>
        <option value="<?= $i ?>" <?= $i===$month?'selected':'' ?>><?= $bulan[$i] ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select form-select-sm">
      <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-3; $y--): ?>
        <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-primary btn-sm">Tampilkan</button>
    <a href="<?= url("/laporan/export?month={$month}&year={$year}") ?>" class="btn btn-success btn-sm">
      <i class="bi bi-file-earmark-excel-fill"></i> Excel
    </a>
  </form>
</div>

<div class="card-soft p-0" style="overflow-x:auto">
  <table class="table table-hover align-middle mb-0">
    <thead style="background:var(--surface-2)">
      <tr>
        <th>NIY</th><th>Nama</th><th>Role</th>
        <th class="text-center">Hadir</th>
        <th class="text-center">Telat</th>
        <th class="text-center">Izin</th>
        <th class="text-center">Sakit</th>
        <th class="text-center">Alpha</th>
        <th class="text-center">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $tot=['hadir'=>0,'telat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0,'total'=>0]; foreach ($rows as $r): ?>
        <?php foreach (array_keys($tot) as $k) $tot[$k] += (int)$r[$k]; ?>
        <tr>
          <td class="text-muted-soft"><?= e($r['niy']) ?></td>
          <td class="fw-semibold"><?= e($r['nama']) ?></td>
          <td><span class="badge bg-light text-dark"><?= e($r['role_name']) ?></span></td>
          <td class="text-center text-success fw-semibold"><?= (int)$r['hadir'] ?></td>
          <td class="text-center text-warning fw-semibold"><?= (int)$r['telat'] ?></td>
          <td class="text-center"><?= (int)$r['izin'] ?></td>
          <td class="text-center text-danger"><?= (int)$r['sakit'] ?></td>
          <td class="text-center text-secondary"><?= (int)$r['alpha'] ?></td>
          <td class="text-center fw-bold"><?= (int)$r['total'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:var(--gradient-soft)">
        <td colspan="3" class="fw-bold">TOTAL</td>
        <td class="text-center fw-bold"><?= $tot['hadir'] ?></td>
        <td class="text-center fw-bold"><?= $tot['telat'] ?></td>
        <td class="text-center fw-bold"><?= $tot['izin'] ?></td>
        <td class="text-center fw-bold"><?= $tot['sakit'] ?></td>
        <td class="text-center fw-bold"><?= $tot['alpha'] ?></td>
        <td class="text-center fw-bold"><?= $tot['total'] ?></td>
      </tr>
    </tfoot>
  </table>
</div>
