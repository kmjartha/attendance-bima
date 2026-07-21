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
    <label class="form-label">Hari Efektif Kerja</label>
    <select class="form-select form-select-sm mb-2" name="hari_aktif_preset" id="hariAktifPreset-<?= $isEdit?'e':'c' ?>" onchange="hariAktifPresetChange(this, '<?= $isEdit?'e':'c' ?>')">
      <option value="Senin,Selasa,Rabu,Kamis,Jumat">Senin – Jumat</option>
      <option value="Senin,Selasa,Rabu,Kamis,Jumat,Sabtu">Senin – Sabtu</option>
      <option value="Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu">Setiap Hari (7 Hari)</option>
      <option value="custom">Custom…</option>
    </select>
    <div id="hariAktifCustom-<?= $isEdit?'e':'c' ?>" style="display:none; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
      <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $i => $d): ?>
        <label class="d-flex align-items-center gap-2" style="padding:.5rem .8rem; margin:0; cursor:pointer; <?= $i > 0 ? 'border-top:1px solid #f1f3f5;' : '' ?>">
          <input class="form-check-input m-0" type="checkbox" name="hari_aktif[]" value="<?= $d ?>" <?= (!$isEdit && in_array($d, ['Senin','Selasa','Rabu','Kamis','Jumat'], true)) ? 'checked' : '' ?>>
          <span><?= $d ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-12">
    <div class="form-check form-switch">
      <input type="checkbox" name="is_active" id="active-<?= $isEdit?'e':'c' ?>" class="form-check-input" checked>
      <label for="active-<?= $isEdit?'e':'c' ?>" class="form-check-label">Shift aktif</label>
    </div>
  </div>
</div>
<script>
if (typeof hariAktifPresetChange !== 'function') {
  function hariAktifPresetChange(sel, ctx) {
    var customBox = document.getElementById('hariAktifCustom-' + ctx);
    if (sel.value === 'custom') { customBox.style.display = 'block'; return; }
    customBox.style.display = 'none';
    var days = sel.value.split(',');
    customBox.querySelectorAll('input[type=checkbox]').forEach(function(cb){
      cb.checked = days.indexOf(cb.value) !== -1;
    });
  }
  function setHariAktif(ctx, days) {
    var presetSel = document.getElementById('hariAktifPreset-' + ctx);
    var customBox = document.getElementById('hariAktifCustom-' + ctx);
    days = days.map(function(d){ return d.trim(); }).filter(function(d){ return d !== ''; });
    var key = days.join(',');
    var known = Array.prototype.some.call(presetSel.options, function(o){ return o.value === key; });
    customBox.querySelectorAll('input[type=checkbox]').forEach(function(cb){
      cb.checked = days.indexOf(cb.value) !== -1;
    });
    if (known) { presetSel.value = key; customBox.style.display = 'none'; }
    else { presetSel.value = 'custom'; customBox.style.display = 'block'; }
  }
  document.addEventListener('DOMContentLoaded', function () {
    setHariAktif('c', ['Senin','Selasa','Rabu','Kamis','Jumat']);
  });
}
</script>
