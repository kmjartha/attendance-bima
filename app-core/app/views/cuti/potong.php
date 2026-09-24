<?php $errors = $_SESSION['_errors'] ?? []; ?>

<div class="page-head mb-3">
  <h2 class="mb-1">Potong Cuti</h2>
  <div class="text-muted-soft">Kurangi jatah cuti karyawan. Karyawan yang bersangkutan akan menerima notifikasi berisi jumlah hari dan alasan pemotongan.</div>
</div>

<form method="get" class="card-soft mb-3" style="max-width:680px">
  <label class="form-label fw-semibold">Pilih Karyawan</label>
  <select name="user_id" class="form-select" onchange="this.form.submit()">
    <option value="">— Pilih Karyawan —</option>
    <?php foreach ($karyawan as $k): ?>
      <option value="<?= (int)$k['id'] ?>" <?= $selectedUserId===(int)$k['id']?'selected':'' ?>>
        <?= e($k['nama']) ?> — <?= e($k['role_name']) ?> (sisa cuti: <?= (int)$k['jumlah_cuti'] ?> hari)
      </option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-sm btn-primary mt-2">Tampilkan</button></noscript>
</form>

<?php if ($selectedUser): ?>
  <?php $sisa = (int)$selectedUser['jumlah_cuti']; ?>

  <div class="card-soft mb-3" style="max-width:680px">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <div class="fw-semibold"><?= e($selectedUser['nama']) ?></div>
        <div class="text-muted-soft" style="font-size:.85rem"><?= e($selectedUser['role_name'] ?? '') ?></div>
      </div>
      <div class="text-end">
        <div class="text-muted-soft" style="font-size:.75rem">Sisa cuti saat ini</div>
        <div class="fw-bold" style="font-size:1.6rem; line-height:1.1"><?= $sisa ?> <span class="fs-6 fw-semibold">hari</span></div>
      </div>
    </div>
  </div>

  <?php if ($sisa < 1): ?>
    <div class="alert alert-warning" style="max-width:680px">
      Sisa cuti karyawan ini sudah 0 hari, sehingga tidak ada yang bisa dipotong.
    </div>
  <?php else: ?>
    <form method="post" action="<?= url('/cuti/potong') ?>" class="card-soft mb-3" style="max-width:680px" id="form-potong-cuti">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">

      <label class="form-label fw-semibold" for="jumlah_hari">Jumlah Hari yang Dipotong</label>
      <div class="input-group mb-1" style="max-width:220px">
        <input type="number" name="jumlah_hari" id="jumlah_hari" min="1" max="<?= $sisa ?>" step="1"
               class="form-control <?= isset($errors['jumlah_hari'])?'is-invalid':'' ?>"
               value="<?= e(old('jumlah_hari', '1')) ?>" required>
        <span class="input-group-text">hari</span>
      </div>
      <?php if (isset($errors['jumlah_hari'])): ?>
        <div class="invalid-feedback d-block mb-2"><?= e($errors['jumlah_hari']) ?></div>
      <?php else: ?>
        <div class="text-muted-soft mb-3" style="font-size:.78rem">Maksimal <?= $sisa ?> hari (sesuai sisa cuti saat ini).</div>
      <?php endif; ?>

      <label class="form-label fw-semibold" for="alasan">Alasan Pemotongan <span class="text-danger">*</span></label>
      <textarea name="alasan" id="alasan" rows="4" maxlength="1000" required
                class="form-control mb-1 <?= isset($errors['alasan'])?'is-invalid':'' ?>"
                placeholder="Contoh: Izin tidak masuk tanggal 12 Agustus tanpa pengajuan cuti."><?= e(old('alasan')) ?></textarea>
      <?php if (isset($errors['alasan'])): ?>
        <div class="invalid-feedback d-block mb-2"><?= e($errors['alasan']) ?></div>
      <?php else: ?>
        <div class="text-muted-soft mb-3" style="font-size:.78rem">Alasan ini akan tampil di notifikasi karyawan.</div>
      <?php endif; ?>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= url('/cuti/potong') ?>" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-danger"><i class="bi bi-calendar-minus"></i> Potong Cuti</button>
      </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('form-potong-cuti');
      if (!form) return;
      const nama = <?= json_encode($selectedUser['nama']) ?>;
      form.addEventListener('submit', (e) => {
        if (form.dataset.confirmed === '1') return;
        e.preventDefault();
        if (!form.reportValidity()) return;
        const n = form.querySelector('[name="jumlah_hari"]').value;
        const go = () => { form.dataset.confirmed = '1'; form.submit(); };
        if (!window.Swal) {
          if (confirm('Potong ' + n + ' hari cuti ' + nama + '?')) go();
          return;
        }
        Swal.fire({
          title: 'Potong ' + n + ' hari cuti?',
          text: nama + ' akan menerima notifikasi pemotongan ini.',
          icon: 'warning', showCancelButton: true,
          confirmButtonText: 'Ya, potong', cancelButtonText: 'Batal',
          confirmButtonColor: '#ef4444',
        }).then(r => { if (r.isConfirmed) go(); });
      });
    });
    </script>
  <?php endif; ?>

  <div class="card-soft" style="max-width:680px">
    <h3 class="h6 mb-3">Riwayat Pemotongan — <?= e($selectedUser['nama']) ?></h3>
    <?php if (empty($history)): ?>
      <div class="text-muted-soft">Belum ada pemotongan cuti.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr><th>Tanggal</th><th class="text-center">Dipotong</th><th class="text-center">Sisa</th><th>Alasan</th><th>Oleh</th></tr>
          </thead>
          <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td class="text-nowrap"><?= e(format_date_id($h['created_at'], true)) ?></td>
              <td class="text-center"><span class="badge bg-danger-subtle text-danger">−<?= (int)$h['jumlah_hari'] ?> hari</span></td>
              <td class="text-center text-nowrap"><?= (int)$h['sisa_sebelum'] ?> → <?= (int)$h['sisa_sesudah'] ?></td>
              <td style="white-space:pre-line"><?= e($h['alasan']) ?></td>
              <td><?= e($h['hr_nama'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>
<?php unset($_SESSION['_old'], $_SESSION['_errors']); ?>
