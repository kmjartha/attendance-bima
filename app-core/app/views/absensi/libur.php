<?php /** @var array|null $holiday */ ?>

<div class="absen-wrap">
  <div class="absen-head">
    <a href="<?= url('/dashboard') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
    <div>
      <div class="title">Absen Masuk</div>
      <div class="sub"><?= e(format_date_id(date('Y-m-d'))) ?> · <span data-clock>--:--:--</span></div>
    </div>
  </div>

  <div class="card-soft text-center" style="padding:2.5rem 1.25rem;background:linear-gradient(135deg,#eef2ff,#f5f3ff)">
    <div style="font-size:3.5rem;line-height:1">🌴</div>
    <h3 class="mt-3 mb-1">Selamat Liburan!</h3>
    <p class="text-muted-soft mb-3">
      <?php if (!empty($leave)): ?>
        Anda sedang dalam <strong><?= e($leave['jenis'] === 'sakit' ? 'sakit' : 'cuti') ?></strong> yang sudah disetujui.
        Rentang: <strong><?= e(format_date_id($leave['tanggal_mulai'])) ?><?= $leave['tanggal_mulai'] !== $leave['tanggal_selesai'] ? ' — ' . e(format_date_id($leave['tanggal_selesai'])) : '' ?></strong>.
        Absensi untuk tanggal ini tidak dapat dilakukan.
      <?php elseif ($holiday): ?>
        Hari ini libur — <strong><?= e($holiday['keterangan']) ?></strong>. Anda tidak perlu dan tidak bisa absen hari ini.
      <?php else: ?>
        Hari ini bukan hari kerja efektif Anda. Anda tidak perlu dan tidak bisa absen hari ini.
      <?php endif; ?>
    </p>
    <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Kembali ke Beranda</a>
  </div>
</div>
