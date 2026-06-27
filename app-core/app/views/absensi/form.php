<?php /** @var array $me, $today, $shift, $user_shifts; @var string $mode; @var bool $has_face; @var float $face_thresh */ ?>

<div class="absen-wrap">
  <div class="absen-head">
    <a href="<?= url('/dashboard') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
    <div>
      <div class="title"><?= $mode === 'keluar' ? 'Absen Pulang' : ($mode==='done' ? 'Absensi Hari Ini' : 'Absen Masuk') ?></div>
      <div class="sub"><?= e(format_date_id(date('Y-m-d'))) ?> · <span data-clock>--:--:--</span></div>
    </div>
  </div>

  <?php if ($mode === 'done'): ?>
    <div class="card-soft text-center" style="padding:2rem 1rem">
      <div style="font-size:3rem;color:var(--success)"><i class="bi bi-check-circle-fill"></i></div>
      <h3 class="mt-2 mb-1">Absensi hari ini selesai</h3>
      <p class="text-muted-soft mb-3">Anda sudah absen masuk <strong><?= e(time_only($today['jam_masuk'])) ?></strong> dan pulang <strong><?= e(time_only($today['jam_keluar'])) ?></strong>.</p>
      <a href="<?= url('/absensi/riwayat') ?>" class="btn btn-primary">Lihat Riwayat</a>
    </div>
  <?php else: ?>

    <?php if (!$has_face): ?>
      <div class="alert alert-warning rounded-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Akun Anda belum memiliki data wajah terdaftar. Hubungi HRD untuk merekam foto profile + face descriptor.
        Absensi tetap bisa dilakukan, namun verifikasi wajah dilewati.
      </div>
    <?php endif; ?>

    <div class="absen-camera-card">
      <div class="cam-stage">
        <video id="cam" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>
        <div class="cam-hud">
          <span class="hud-pill" id="hudFace"><i class="bi bi-person-bounding-box"></i> Mendeteksi wajah…</span>
          <span class="hud-pill" id="hudGps"><i class="bi bi-geo-alt"></i> Mengambil GPS…</span>
        </div>
      </div>

      <?php if ($shift): ?>
        <div class="meta-row mt-3">
          <span class="lbl">Shift:</span>
          <span class="val"><?= e($shift['nama']) ?> (<?= e(substr($shift['jam_masuk'],0,5)) ?>–<?= e(substr($shift['jam_keluar'],0,5)) ?>, toleransi <?= (int)$shift['toleransi_menit'] ?>′)</span>
        </div>
      <?php endif; ?>

      <div class="cam-meta" style="display:none">
        <div class="meta-row">
          <span class="lbl">Lokasi:</span>
          <span class="val" id="metaLoc">—</span>
        </div>
        <div class="meta-row">
          <span class="lbl">Jarak ke kantor:</span>
          <span class="val" id="metaDist">—</span>
        </div>
        <div class="meta-row">
          <span class="lbl">Skor wajah:</span>
          <span class="val" id="metaFace">—</span>
        </div>
        <?php if ($shift): ?>
          <div class="meta-row">
            <span class="lbl">Shift:</span>
            <span class="val"><?= e($shift['nama']) ?> (<?= e(substr($shift['jam_masuk'],0,5)) ?>–<?= e(substr($shift['jam_keluar'],0,5)) ?>, toleransi <?= (int)$shift['toleransi_menit'] ?>′)</span>
          </div>
        <?php endif; ?>
      </div>

      <button id="btnSubmit" class="btn-absen" disabled
              data-type="<?= e($mode) ?>"
              data-radius="<?= (int)$me['radius_meter'] ?>"
              data-lat="<?= e($me['latitude_kantor']) ?>"
              data-lng="<?= e($me['longitude_kantor']) ?>"
              data-has-face="<?= $has_face ? '1':'0' ?>"
              data-threshold="<?= e($face_thresh) ?>">
        <i class="bi bi-camera-fill"></i>
        <span><?= $mode === 'keluar' ? 'Absen Pulang Sekarang' : 'Absen Masuk Sekarang' ?></span>
      </button>
      <div class="text-center text-muted-soft mt-2" style="font-size:.78rem">
        Pastikan wajah jelas, pencahayaan cukup, dan GPS aktif.
      </div>
    </div>

    <?php if ($today && $today['jam_masuk']): ?>
      <div class="card-soft mt-3">
        <h3 style="font-size:1rem">Absen Masuk Hari Ini</h3>
        <div class="d-flex align-items-center gap-3">
          <?php if (!empty($today['foto_masuk'])): ?>
            <img src="<?= e(upload_url($today['foto_masuk'])) ?>" style="width:64px;height:64px;border-radius:12px;object-fit:cover" alt="Foto absen masuk">
          <?php else: ?>
            <div style="width:64px;height:64px;border-radius:12px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#bbb">
              <i class="bi bi-image"></i>
            </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:700"><?= e(time_only($today['jam_masuk'])) ?></div>
            <div class="text-muted-soft" style="font-size:.78rem">Status: <?= status_badge($today['status']) ?></div>
            <?php if ($today['face_match_masuk']!==null): ?>
              <div class="text-muted-soft" style="font-size:.78rem">Skor wajah: <?= number_format((float)$today['face_match_masuk'],1) ?>%</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php if ($mode !== 'done'): ?>
  <!-- face-api + absensi loader -->
  <script>
    window.SIABSEN = window.SIABSEN || {};
    window.SIABSEN.absensi = {
      submitUrl     : <?= json_encode(url('/absensi/submit')) ?>,
      modelsUrl     : <?= json_encode(asset('models')) ?>,
      storedDesc    : <?= $me['face_descriptor'] ? $me['face_descriptor'] : 'null' ?>,
      threshold     : <?= (float)$face_thresh ?>,
      hasFace       : <?= $has_face ? 'true':'false' ?>,
      csrf          : <?= json_encode(csrf_token()) ?>,
      shiftStart    : <?= $shift ? json_encode($shift['jam_masuk']) : 'null' ?>,
      shiftTolerance: <?= $shift ? (int)$shift['toleransi_menit'] : 0 ?>,
      userShifts    : <?= json_encode($user_shifts ?? []) ?>
    };
  </script>
  <script src="<?= asset('vendor/face-api/face-api.min.js') ?>"></script>
  <script src="<?= asset('js/absensi.js') ?>"></script>
<?php endif; ?>
