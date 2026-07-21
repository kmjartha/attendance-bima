<?php $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-1">Laporan Pribadi</h2>
    <div class="text-muted-soft"><?= e($bulan[$month]) ?> <?= (int)$year ?></div>
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
  </form>
</div>

<div class="row g-2 mb-3">
  <?php foreach ([['hadir','Hadir','success'],['telat','Telat','warning'],['izin','Izin','info'],['sakit','Sakit','danger'],['alpha','Alpha','secondary']] as $s): ?>
    <div class="col-6 col-md">
      <div class="card-soft p-3 text-center">
        <div class="text-muted-soft" style="font-size:.72rem"><?= $s[1] ?></div>
        <div class="fw-bold text-<?= $s[2] ?>" style="font-size:1.4rem"><?= (int)$summary[$s[0]] ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-soft">
      <h3 style="font-size:1rem">Kehadiran Harian</h3>
      <canvas id="chartLine" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft">
      <h3 style="font-size:1rem">Distribusi Status</h3>
      <canvas id="chartPie" height="160"></canvas>
    </div>
  </div>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function(){
  const labels = <?= json_encode($labels) ?>;
  const series = <?= json_encode($hadirSeries) ?>;
  new Chart(document.getElementById('chartLine'), {
    type: 'line',
    data: { labels, datasets: [{ label: 'Hadir/Telat', data: series, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.12)', fill:true, tension:.3, stepped:true }] },
    options: { plugins:{legend:{display:false}}, scales:{ y:{ min:0, max:1, ticks:{ stepSize:1 } } } }
  });
  new Chart(document.getElementById('chartPie'), {
    type: 'doughnut',
    data: {
      labels: ['Hadir','Telat','Izin','Sakit','Alpha'],
      datasets: [{
        data: <?= json_encode([(int)$summary['hadir'],(int)$summary['telat'],(int)$summary['izin'],(int)$summary['sakit'],(int)$summary['alpha']]) ?>,
        backgroundColor: ['#2563eb','#f59e0b','#06b6d4','#ef4444','#94a3b8']
      }]
    },
    options: { plugins:{legend:{position:'bottom'}} }
  });
})();
</script>
