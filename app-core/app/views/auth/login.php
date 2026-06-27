<div class="login-shell">
  <aside class="login-hero">
    <div class="brand">
      <div class="logo">SA</div>
      <div>
        <div class="name">SiAbsen</div>
        <div class="tag">Sistem Absensi BiMa School</div>
      </div>
    </div>

    <div class="blurb">
      <h1>Selamat datang<br>kembali.</h1>
    </div>

    <div class="features">
      <div><i class="bi bi-camera-fill"></i> Absensi selfie + verifikasi wajah</div>
      <div><i class="bi bi-geo-alt-fill"></i> Validasi GPS lokasi sekolah</div>
    </div>
  </aside>

  <main class="login-form-wrap">
    <form class="login-form" method="post" action="<?= url('/login') ?>" autocomplete="on">
      <?= csrf_field() ?>

      <h2>Masuk</h2>
      <p class="subt">Gunakan NIY dan password Anda untuk melanjutkan.</p>

      <?php if (!empty($error)): ?>
        <div class="alert-soft">
          <i class="bi bi-exclamation-circle-fill"></i><span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <div class="form-field">
        <label for="niy">NIY</label>
        <input class="form-control-clean" type="text" id="niy" name="niy"
               value="<?= e($old_niy ?? '') ?>" required autofocus
               placeholder="Contoh: 01520052018">
      </div>

      <div class="form-field password-field">
        <label for="password">Password</label>
        <input class="form-control-clean" type="password" id="password" name="password"
               required placeholder="••••••••••" data-password-toggle>
        <button type="button" class="password-toggle-text" aria-label="Tampilkan password">Tampilkan password</button>
      </div>

      <div class="remember-row">
        <label><input type="checkbox" name="remember" value="1" <?= !empty($old_niy) ? 'checked' : '' ?>> Ingat saya</label>
      </div>

      <button type="submit" class="submit-btn">
        Masuk ke Dashboard <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </form>
  </main>
</div>
