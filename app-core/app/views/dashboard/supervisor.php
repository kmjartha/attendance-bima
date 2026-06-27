<div class="card-hero mb-4">
  <h2>Halo, <?= e(user()['nama']) ?> 👋</h2>
  <p>Anda memiliki akses terbatas sebagai Supervisor. Berikut ringkasan verifikasi cuti Manajerial.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card-stat">
      <div class="icon warning"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="label">Cuti Manajerial Pending</div><div class="value"><?= (int)$pending_count ?></div></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card-stat">
      <div class="icon info"><i class="bi bi-bell-fill"></i></div>
      <div><div class="label">Notifikasi Belum Dibaca</div><div class="value"><?= (int)$unread_count ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-soft">
      <h3 class="mb-3">Tugas Utama</h3>
      <p>Supervisor hanya dapat melihat dan memverifikasi pengajuan cuti dari karyawan dengan peran Manajerial.</p>
      <a href="<?= url('/verifikasi-cuti') ?>" class="btn btn-primary">Lihat Verifikasi Cuti</a>
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
