<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title ?? 'Masuk') ?> — <?= e(\App\Core\App::$config['short'] ?? 'SiAbsen') ?></title>
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <meta name="theme-color" content="#2563eb">
</head>
<body>
  <?= $content ?>
  <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
