<?php
  $u = user();
  $unreadCount = 0;
  try {
      $unreadCount = (new \App\Models\NotificationRead())->unreadCountFor((int)$u['id']);
  } catch (\Throwable $e) { /* tabel belum dimigrasi → 0 */ }
?>
<header class="mobile-header">
  <div class="d-flex align-items-center justify-content-between">
    <div>
      <div class="greet">
        <?php
          $h = (int)date('H');
          echo $h < 11 ? 'Selamat pagi 👋' : ($h < 15 ? 'Selamat siang ☀️' : ($h < 18 ? 'Selamat sore 🌤️' : 'Selamat malam 🌙'));
        ?>
      </div>
      <div class="name"><?= e($u['nama'] ?? '') ?></div>
      <div class="role"><?= e($u['role_name']) ?> · NIY <?= e($u['niy']) ?></div>
    </div>
    <div class="mh-actions d-flex align-items-center gap-2">
      <a href="<?= url('/profile') ?>" class="avatar" title="Profil Saya">
        <?= e(initials($u['nama'] ?? '?')) ?>
      </a>
    </div>
  </div>
</header>
