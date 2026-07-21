<div class="card-hero mb-4">
  <h2>Halo, <?= e(user()['nama']) ?> 👋</h2>
  <p>Berikut ringkasan absensi sekolah hari ini, <?= e(format_date_id(date('Y-m-d'))) ?>.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card-stat">
      <div class="icon"><i class="bi bi-people-fill"></i></div>
      <div><div class="label">Total Karyawan Aktif</div><div class="value"><?= (int)$stats['total_karyawan'] ?></div></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card-stat">
      <div class="icon success"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="label">Hadir Hari Ini</div><div class="value"><?= (int)($stats['today']['hadir'] ?? 0) ?></div></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card-stat">
      <div class="icon warning"><i class="bi bi-clock-fill"></i></div>
      <div><div class="label">Telat Hari Ini</div><div class="value"><?= (int)($stats['today']['telat'] ?? 0) ?></div></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card-stat">
      <div class="icon info"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="label">Cuti Pending</div><div class="value"><?= (int)$stats['pending_cuti'] ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card-soft">
      <h3 class="mb-3">Absensi Pribadi Hari Ini</h3>
      <?php if (!$today): ?>
        <div class="empty-state">
          <i class="bi bi-camera"></i>
          <div>Anda belum melakukan absensi masuk.</div>
          <a href="<?= url('/absensi') ?>" class="btn btn-primary mt-3"><i class="bi bi-camera-fill"></i> Absen sekarang</a>
        </div>
      <?php else: ?>
        <p>Jam masuk: <strong><?= e(time_only($today['jam_masuk'])) ?></strong></p>
        <p>Jam keluar: <strong><?= e($today['jam_keluar'] ? time_only($today['jam_keluar']) : '-') ?></strong></p>
        <p>Status: <?= status_badge($today['status']) ?></p>
        <?php if (!$today['jam_keluar'] && $today['jam_masuk']): ?>
          <a href="<?= url('/absensi') ?>" class="btn btn-outline-primary mt-3">Absen pulang</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-soft">
      <h3 class="mb-3">Trend Kehadiran 7 Hari Terakhir</h3>
      <canvas id="trendChart" height="110"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-megaphone-fill text-primary"></i> Pengumuman</h3>
      <?php if (empty($announcements)): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i><div>Belum ada pengumuman.</div></div>
      <?php else: foreach ($announcements as $a): ?>
        <div class="announce-banner">
          <i class="bi bi-pin-angle-fill"></i>
          <div>
            <div class="judul"><?= e($a['judul']) ?></div>
            <div class="isi"><?= e(mb_substr($a['isi'], 0, 100)) ?><?= mb_strlen($a['isi'])>100?'…':'' ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function(){
  const t = <?= json_encode($trend7) ?>;
  new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
      labels: t.labels,
      datasets: [
        { label: 'Hadir', data: t.hadir, backgroundColor:'#2563eb', borderRadius:6 },
        { label: 'Telat', data: t.telat, backgroundColor:'#f59e0b', borderRadius:6 },
        { label: 'Izin/Sakit/Alpha', data: t.absen, backgroundColor:'#ef4444', borderRadius:6 },
      ]
    },
    options: {
      responsive:true,
      plugins:{ legend:{ position:'bottom' } },
      scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
    }
  });
})();
</script>
