<div class="mobile-clock-card">
  <div class="time" data-clock>--:--:--</div>
  <div class="date"><?= e(format_date_id(date('Y-m-d'))) ?></div>
</div>

<div class="mobile-stats-row">
  <div class="mobile-stat">
    <div class="lbl">Streak</div>
    <div class="val green"><?= (int)$streak ?><small>hr</small></div>
  </div>
  <div class="mobile-stat">
    <div class="lbl">Sisa Cuti</div>
    <div class="val"><?= (int)$me['jumlah_cuti'] ?><small>hr</small></div>
  </div>
  <div class="mobile-stat">
    <div class="lbl">Jam Minggu</div>
    <div class="val amber"><?= number_format((float)$jam_minggu,1) ?><small>jam</small></div>
  </div>
</div>

<div class="card-soft mb-3" style="padding:1rem 1.1rem">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div style="font-weight:600;font-size:.92rem">Status Hari Ini</div>
    <?php if ($today): ?><?= status_badge($today['status']) ?><?php endif; ?>
  </div>
  <?php if (!$today): ?>
    <div class="text-muted-soft" style="font-size:.85rem">
      <i class="bi bi-info-circle"></i> Anda belum absen masuk hari ini.
    </div>
  <?php else: ?>
    <div class="today-status">
      <div class="col-time">
        <div class="lbl">Masuk</div>
        <div class="val"><?= e(time_only($today['jam_masuk'])) ?></div>
      </div>
      <div class="col-time">
        <div class="lbl">Pulang</div>
        <div class="val"><?= $today['jam_keluar'] ? e(time_only($today['jam_keluar'])) : '—' ?></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="mobile-section-title">Aksi Cepat</div>
<div class="mobile-action-grid">
  <a href="<?= url('/absensi') ?>" class="mobile-action-btn primary">
    <div class="ico"><i class="bi bi-camera-fill"></i></div>
    <div class="label"><?= $today && $today['jam_masuk'] && !$today['jam_keluar'] ? 'Absen Pulang' : 'Absen Masuk' ?></div>
    <div class="sub">Selfie + GPS</div>
  </a>
  <a href="<?= url('/absensi/riwayat') ?>" class="mobile-action-btn">
    <div class="ico"><i class="bi bi-clock-history"></i></div>
    <div class="label">Riwayat</div>
    <div class="sub">Histori absensi</div>
  </a>
  <a href="<?= url('/cuti') ?>" class="mobile-action-btn">
    <div class="ico"><i class="bi bi-calendar-event"></i></div>
    <div class="label">Ajukan Cuti</div>
    <div class="sub">Sakit / Tahunan</div>
  </a>
  <a href="<?= url('/laporan') ?>" class="mobile-action-btn">
    <div class="ico"><i class="bi bi-bar-chart-fill"></i></div>
    <div class="label">Laporan</div>
    <div class="sub">Statistik bulanan</div>
  </a>
</div>

<?php /* Pengumuman dipindah ke halaman /notifikasi (lihat ikon bell di header) */ ?>
