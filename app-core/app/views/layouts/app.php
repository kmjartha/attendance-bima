<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title ?? 'SiAbsen') ?> — <?= e(\App\Core\App::$config['short'] ?? 'SiAbsen') ?></title>
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <meta name="theme-color" content="#2563eb">
  <script>
    window.SIABSEN = window.SIABSEN || {};
    window.SIABSEN.paths = {
      modelsUrl: <?= json_encode(asset('models')) ?>,
      swUrl: <?= json_encode(url('sw-face-models.js')) ?>
    };
  </script>
  <script src="<?= asset('js/face-model-preload.js') ?>"></script>
</head>
<body>
<div class="app-shell">
  <?php include APP_PATH . '/views/partials/sidebar.php'; ?>

  <div class="app-main">
    <header class="topbar">
      <button class="toggle" data-sidebar-toggle aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="pagetitle"><?= e($title ?? '') ?></div>

      <?php
        $latest = (new \App\Models\Announcement())->published(1);
        if (!empty($latest)):
      ?>
        <div class="announce-pill" title="<?= e($latest[0]['judul']) ?>">
          <i class="bi bi-megaphone-fill"></i>
          <span><?= e($latest[0]['judul']) ?></span>
        </div>
      <?php endif; ?>
    </header>

    <main class="app-content">
      <?php if ($f = flash('success')): ?>
        <div class="alert alert-success rounded-3 mb-3"><?= e($f) ?></div>
      <?php endif; ?>
      <?php if ($f = flash('error')): ?>
        <div class="alert alert-danger rounded-3 mb-3"><?= e($f) ?></div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>
</div>

<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
