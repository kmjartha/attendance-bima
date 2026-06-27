<?php $u = user(); $role = user_role(); ?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo">SA</div>
    <div>
      <div class="name">SiAbsen</div>
      <div class="tag">Absensi Sekolah</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="group-label">Menu Utama</div>
    <a href="<?= url('/dashboard') ?>" class="<?= is_active('/dashboard') ?>">
      <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>

    <?php if (in_array($role, ['HRD','Guru','Staff','Security','Kepsek','Manajerial'], true)): ?>
      <a href="<?= url('/absensi') ?>" class="<?= is_active_exact('/absensi') ?>">
        <i class="bi bi-camera-fill"></i> Absensi
      </a>
      <a href="<?= url('/absensi/riwayat') ?>" class="<?= is_active('/absensi/riwayat') ?>">
        <i class="bi bi-clock-history"></i> Riwayat Absensi
      </a>
      <a href="<?= url('/cuti') ?>" class="<?= is_active('/cuti') ?>">
        <i class="bi bi-calendar-event"></i> Cuti / Sakit
      </a>
    <?php endif; ?>

    <a href="<?= url('/notifikasi') ?>" class="<?= is_active('/notifikasi') ?>">
      <i class="bi bi-bell-fill"></i> Notifikasi
      <?php
        try {
          $__nu = (new \App\Models\NotificationRead())->unreadCountFor((int)$u['id']);
          if ($__nu > 0) echo '<span class="badge bg-danger ms-auto">'.($__nu>9?'9+':$__nu).'</span>';
        } catch (\Throwable $e) {}
      ?>
    </a>

    <?php if (in_array($role, ['HRD','Supervisor'], true)): ?>
      <div class="group-label">Master Data</div>
      <a href="<?= url('/karyawan') ?>" class="<?= is_active('/karyawan') ?>">
        <i class="bi bi-people-fill"></i> Karyawan
      </a>
      <a href="<?= url('/shift') ?>" class="<?= is_active('/shift') ?>">
        <i class="bi bi-clock-fill"></i> Shift Kerja
      </a>
      <a href="<?= url('/pengumuman') ?>" class="<?= is_active('/pengumuman') ?>">
        <i class="bi bi-megaphone-fill"></i> Pengumuman
      </a>
    <?php endif; ?>

    <?php if (in_array($role, ['HRD','Supervisor','Kepsek'], true)): ?>
      <div class="group-label">Pengelolaan</div>
      <a href="<?= url('/verifikasi-cuti') ?>" class="<?= is_active('/verifikasi-cuti') ?>">
        <i class="bi bi-check2-square"></i> Verifikasi Cuti
        <?php
          try {
            $pendingCount = 0;
            $leaveModel = new \App\Models\LeaveRequest();
            if (has_role('HRD')) {
              $pendingCount = $leaveModel->pendingCountForRoles(['Kepsek','Staff','Guru','Security']);
            } elseif (has_role('Supervisor')) {
              $pendingCount = $leaveModel->pendingCountForRoles(['HRD','Manajerial']);
            } else {
              $pendingCount = $leaveModel->pendingCountForRoles(['Staff','Guru','Security']);
            }
            if ($pendingCount > 0) {
              echo '<span class="badge bg-danger ms-auto">'.($pendingCount > 9 ? '9+' : $pendingCount).'</span>';
            }
          } catch (\Throwable $e) {}
        ?>
      </a>
      <?php if (has_role('HRD')): ?>
        <a href="<?= url('/laporan/harian') ?>" class="<?= is_active('/laporan/harian') ?>">
          <i class="bi bi-calendar-day-fill"></i> Laporan Harian
        </a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array($role, ['HRD','Kepsek'], true)): ?>
      <?php $__uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); ?>
      <a href="<?= url('/laporan/karyawan') ?>" class="<?= is_active('/laporan/karyawan') ?>">
        <i class="bi bi-person-lines-fill"></i> Laporan Karyawan
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <a href="<?= url('/profile') ?>" class="avatar" title="Profil Saya"><?= e(initials($u['nama'] ?? '?')) ?></a>
      <div class="info">
        <div class="nm"><?= e($u['nama'] ?? '') ?></div>
        <div class="rl"><?= e($u['role_name'] ?? '') ?> &middot; <?= e($u['niy'] ?? '') ?></div>
      </div>
      <a href="<?= url('/logout') ?>" class="text-muted-soft" title="Logout">
        <i class="bi bi-box-arrow-right fs-5"></i>
      </a>
    </div>
  </div>
</aside>
