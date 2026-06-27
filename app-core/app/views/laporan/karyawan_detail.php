<?php
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$totalHari = array_sum(array_map('intval', $summary));
?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <div class="text-muted-soft" style="font-size:.85rem">
      <a href="<?= url('/laporan/karyawan') ?>" class="text-decoration-none">
        <i class="bi bi-arrow-left"></i> Kembali ke daftar
      </a>
    </div>
    <h2 class="mb-1 mt-1"><?= e($karyawan['nama']) ?></h2>
    <div class="text-muted-soft">
      NIY <b><?= e($karyawan['niy']) ?></b> · <?= e($karyawan['role_name']) ?>
      <?php if (!empty($karyawan['jabatan'])): ?> · <?= e($karyawan['jabatan']) ?><?php endif; ?>
    </div>
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
    <a class="btn btn-success btn-sm"
       href="<?= url("/laporan/karyawan/{$karyawan['id']}/export?month={$month}&year={$year}") ?>">
      <i class="bi bi-file-earmark-excel-fill"></i> Excel
    </a>
  </form>
</div>

<div class="row g-3 mb-3">
  <?php
    $cards = [
      ['Hadir',   $summary['hadir'], 'success', 'bi-check-circle-fill'],
      ['Telat',   $summary['telat'], 'warning', 'bi-clock-fill'],
      ['Izin',    $summary['izin'],  'info',    'bi-envelope-paper-fill'],
      ['Sakit',   $summary['sakit'], 'danger',  'bi-bandaid-fill'],
      ['Alpha',   $summary['alpha'], 'secondary','bi-x-octagon-fill'],
      ['Total',   $totalHari,        'primary', 'bi-calendar3'],
    ];
    foreach ($cards as [$lbl,$val,$tone,$ico]):
  ?>
    <div class="col-6 col-md-2">
      <div class="card-soft p-3 text-center">
        <div class="text-<?= $tone ?>" style="font-size:1.4rem"><i class="bi <?= $ico ?>"></i></div>
        <div class="fw-bold" style="font-size:1.4rem"><?= (int)$val ?></div>
        <div class="text-muted-soft" style="font-size:.78rem"><?= $lbl ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <div class="fw-semibold mb-2">Tren Harian — <?= e($bulan[$month]) ?> <?= (int)$year ?></div>
      <canvas id="chartHadir" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft p-3">
      <div class="fw-semibold mb-2">Distribusi Status</div>
      <canvas id="chartPie" height="120"></canvas>
    </div>
  </div>
</div>

<div class="card-soft p-0 mt-3" style="overflow-x:auto">
  <div class="p-3 fw-semibold border-bottom">Riwayat Absensi</div>
  <table class="table align-middle mb-0">
    <thead style="background:var(--surface-2)">
      <tr>
        <th>Tanggal</th><th>Shift</th><th>Masuk</th><th>Menit Telat</th><th>Pulang</th>
        <th class="text-center">Status</th><th class="text-end">Match</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$history): ?>
        <tr><td colspan="6" class="text-center text-muted-soft py-4">Belum ada absensi pada periode ini.</td></tr>
      <?php endif; ?>
      <?php foreach ($history as $h): ?>
        <tr>
          <td><?= e(format_date_id($h['tanggal'])) ?></td>
          <td class="text-muted-soft"><?= e($h['shift_nama'] ?? '-') ?></td>
          <td><?= e(time_only($h['jam_masuk'])) ?></td>
          <td class="text-center"><?= isset($h['terlambat_menit']) && $h['terlambat_menit']!==null ? (int)$h['terlambat_menit'] : '—' ?></td>
          <td><?= $h['jam_keluar'] ? e(time_only($h['jam_keluar'])) : '—' ?></td>
          <td class="text-center"><?= status_badge($h['status']) ?></td>
          <td class="text-end text-muted-soft">
            <?= isset($h['face_match_score']) && $h['face_match_score']!==null
                ? number_format((float)$h['face_match_score'],3) : '—' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function(){
  const labels = <?= json_encode($labels) ?>;
  const hadir  = <?= json_encode($hadirSeries) ?>;
  const telat  = <?= json_encode($telatSeries) ?>;
  new Chart(document.getElementById('chartHadir'), {
    type: 'bar',
    data: { labels, datasets: [
      { label:'Hadir', data:hadir, backgroundColor:'#2563eb' },
      { label:'Telat', data:telat, backgroundColor:'#f59e0b' },
    ]},
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, max:1, ticks:{stepSize:1} }, x:{ stacked:true } } }
  });
  new Chart(document.getElementById('chartPie'), {
    type:'doughnut',
    data:{ labels:['Hadir','Telat','Izin','Sakit','Alpha'],
      datasets:[{ data:[
        <?= (int)$summary['hadir'] ?>, <?= (int)$summary['telat'] ?>,
        <?= (int)$summary['izin']  ?>, <?= (int)$summary['sakit'] ?>,
        <?= (int)$summary['alpha'] ?>
      ], backgroundColor:['#2563eb','#f59e0b','#0ea5e9','#ef4444','#94a3b8'] }]
    },
    options:{ plugins:{ legend:{ position:'bottom' } } }
  });
})();
</script>
