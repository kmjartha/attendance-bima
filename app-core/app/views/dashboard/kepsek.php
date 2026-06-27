<div class="card-hero mb-4">
  <h2>Selamat datang, <?= e(user()['nama']) ?></h2>
  <p>Pantau kehadiran tim guru dan kelola persetujuan cuti dari sini.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card-stat">
      <div class="icon success"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="label">Hadir Hari Ini</div><div class="value"><?= (int)($today_stats['hadir'] ?? 0) ?></div></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card-stat">
      <div class="icon warning"><i class="bi bi-clock-fill"></i></div>
      <div><div class="label">Telat Hari Ini</div><div class="value"><?= (int)($today_stats['telat'] ?? 0) ?></div></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card-stat">
      <div class="icon danger"><i class="bi bi-x-circle-fill"></i></div>
      <div><div class="label">Sakit / Izin</div><div class="value"><?= (int)(($today_stats['izin']??0)+($today_stats['sakit']??0)) ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-soft">
      <h3 class="mb-3">Trend Kehadiran 7 Hari</h3>
      <canvas id="trendChart" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft mb-3">
      <h3 class="mb-3">Absensi Pribadi Hari Ini</h3>
      <?php if (!$today): ?>
        <div class="empty-state">
          <i class="bi bi-camera"></i>
          <div>Anda belum melakukan absensi masuk.</div>
          <a href="<?= url('/absensi') ?>" class="btn btn-primary mt-3"><i class="bi bi-camera-fill"></i> Absen sekarang</a>
        </div>
      <?php else: ?>
        <p>Jam masuk: <strong><?= e(time_only($today['jam_masuk'])) ?></strong></p>
        <p>Jam keluar: <strong><?= e(time_only($today['jam_keluar'])) ?></strong></p>
        <p>Status: <?= status_badge($today['status']) ?></p>
      <?php endif; ?>
    </div>
    <div class="card-soft">
      <h3 class="mb-3"><i class="bi bi-megaphone-fill text-primary"></i> Pengumuman</h3>
      <?php if (empty($announcements)): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i><div>Belum ada pengumuman.</div></div>
      <?php else: foreach (array_slice($announcements,0,3) as $a): ?>
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
    type: 'line',
    data: { labels: t.labels, datasets: [
      { label:'Hadir', data:t.hadir, borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,.12)', tension:.35, fill:true },
      { label:'Telat', data:t.telat, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.12)', tension:.35, fill:true },
    ]},
    options: { plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ beginAtZero:true } } }
  });
})();
</script>
