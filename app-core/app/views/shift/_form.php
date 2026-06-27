<?php $isEdit = $isEdit ?? false; ?>
<div class="row g-2">
  <div class="col-12">
    <label class="form-label">Nama Shift</label>
    <input type="text" name="nama" class="form-control" required maxlength="64" placeholder="Shift Pagi">
  </div>
  <div class="col-6">
    <label class="form-label">Jam Masuk</label>
    <input type="time" name="jam_masuk" class="form-control" required>
  </div>
  <div class="col-6">
    <label class="form-label">Jam Keluar</label>
    <input type="time" name="jam_keluar" class="form-control" required>
  </div>
  <div class="col-6">
    <label class="form-label">Toleransi Telat (menit)</label>
    <input type="number" name="toleransi_menit" class="form-control" min="0" max="120" value="15" required>
  </div>
  <div class="col-6">
    <label class="form-label">Cut-Off Tanggal</label>
    <input type="number" name="cut_off_tanggal" class="form-control" min="1" max="28" value="25" required>
  </div>
  <div class="col-12">
    <div class="form-check form-switch">
      <input type="checkbox" name="is_active" id="active-<?= $isEdit?'e':'c' ?>" class="form-check-input" checked>
      <label for="active-<?= $isEdit?'e':'c' ?>" class="form-check-label">Shift aktif</label>
    </div>
  </div>
</div>
