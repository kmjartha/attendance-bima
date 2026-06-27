<?php /** @var array $rows; @var int $month, $year; @var array $summary */
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<div class="page-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="mb-1">Riwayat Absensi</h2>
    <div class="text-muted-soft">Periode: <?= e($bulan[$month]) ?> <?= (int)$year ?></div>
  </div>
  <form method="get" class="d-flex gap-2">
    <select name="month" class="form-select form-select-sm">
      <?php for ($i=1;$i<=12;$i++): ?>
        <option value="<?= $i ?>" <?= $i===$month?'selected':'' ?>><?= $bulan[$i] ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select form-select-sm">
      <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-3; $y--): ?>
        <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-primary btn-sm">Tampilkan</button>
  </form>
</div>

<div class="row g-2 mb-3">
  <?php foreach ([
      ['hadir','Hadir','success','check-circle-fill'],
      ['telat','Telat','warning','clock-fill'],
      ['izin','Izin','info','envelope-paper-fill'],
      ['sakit','Sakit','danger','bandaid-fill'],
      ['alpha','Alpha','secondary','x-circle-fill'],
    ] as $s): ?>
    <div class="col-6 col-md">
      <div class="card-soft p-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-<?= $s[3] ?> text-<?= $s[2] ?>" style="font-size:1.25rem"></i>
          <div>
            <div class="text-muted-soft" style="font-size:.72rem"><?= $s[1] ?></div>
            <div style="font-weight:700;font-size:1.15rem"><?= (int)$summary[$s[0]] ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (empty($rows)): ?>
  <div class="card-soft text-center" style="padding:2rem">
    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--text-soft)"></i>
    <h3 class="mt-2 mb-1" style="font-size:1rem">Belum ada absensi pada periode ini</h3>
    <p class="text-muted-soft mb-0">Pilih bulan/tahun lain atau lakukan absensi dulu.</p>
  </div>
<?php else: ?>
  <div class="card-soft p-0" style="overflow:hidden">
    <?php foreach ($rows as $r): ?>
      <div class="riwayat-row">
        <div class="r-date">
          <div class="day"><?= (int)date('d', strtotime($r['tanggal'])) ?></div>
          <div class="mon"><?= e(strtoupper(substr($bulan[(int)date('n',strtotime($r['tanggal']))],0,3))) ?></div>
        </div>
        <div class="r-times">
          <div class="r-row"><span>Masuk</span><b><?= e(time_only($r['jam_masuk'])) ?></b></div>
          <div class="r-row"><span>Pulang</span><b><?= e(time_only($r['jam_keluar'])) ?></b></div>
          <?php if ($r['shift_nama']): ?>
            <div class="r-row text-muted-soft" style="font-size:.72rem"><span>Shift</span><b><?= e($r['shift_nama']) ?></b></div>
          <?php endif; ?>
        </div>
        <div class="r-thumbs">
          <?php if ($r['foto_masuk']): ?>
            <a href="<?= e(upload_url($r['foto_masuk'])) ?>" target="_blank" title="Foto masuk">
              <img src="<?= e(upload_url($r['foto_masuk'])) ?>" alt="masuk">
            </a>
          <?php endif; ?>
          <?php if ($r['foto_keluar']): ?>
            <a href="<?= e(upload_url($r['foto_keluar'])) ?>" target="_blank" title="Foto pulang">
              <img src="<?= e(upload_url($r['foto_keluar'])) ?>" alt="keluar">
            </a>
          <?php endif; ?>
        </div>
        <div class="r-meta">
          <?= status_badge($r['status']) ?>
          <?php if ((int)$r['terlambat_menit'] > 0): ?>
            <div class="text-warning mt-1" style="font-size:.78rem"><i class="bi bi-clock-fill"></i> Telat <?= (int)$r['terlambat_menit'] ?> menit</div>
          <?php endif; ?>
          <?php if (!empty($r['keterangan'])): ?>
            <div class="text-muted-soft mt-1" style="font-size:.78rem"><i class="bi bi-chat-left-text"></i> <?= e($r['keterangan']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
