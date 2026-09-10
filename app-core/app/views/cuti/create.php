<?php $errors = $_SESSION['_errors'] ?? []; ?>
<style>
.cuti-cal { border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; background: var(--surface-2); }
.cuti-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.cuti-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cuti-cal-dow { text-align: center; font-size: .72rem; font-weight: 600; color: var(--text-soft); padding: 4px 0; }
.cuti-cal-pad { visibility: hidden; }
.cuti-cal-day {
  border: none; background: var(--surface); border-radius: var(--radius-sm);
  padding: 8px 0; font-size: .85rem; color: var(--text); cursor: pointer;
  transition: var(--transition);
}
.cuti-cal-day:hover { background: var(--primary-soft); }
.cuti-cal-day.is-sunday { color: var(--danger); }
.cuti-cal-day.is-holiday { background: var(--warning); color: #fff; opacity: .85; }
.cuti-cal-day.is-today { box-shadow: 0 0 0 2px var(--primary) inset; font-weight: 700; }
.cuti-cal-day.is-selected { background: var(--primary); color: #fff; font-weight: 600; }
.cuti-cal-day.is-blocked { background: var(--surface-2); color: var(--text-soft); text-decoration: line-through; cursor: not-allowed; opacity: .6; }
.cuti-cal-day.is-blocked:hover { background: var(--surface-2); }
.cuti-cal-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 10px; font-size: .72rem; color: var(--text-muted); }
.cuti-cal-legend .cuti-cal-dot {
  display: inline-block; width: 10px; height: 10px; border-radius: 50%;
  margin-right: 4px; vertical-align: middle;
}
.cuti-cal-legend .cuti-cal-dot.is-holiday { background: var(--warning); }
.cuti-cal-legend .cuti-cal-dot.is-selected { background: var(--primary); }
.cuti-cal-legend .cuti-cal-dot.is-blocked { background: var(--surface-2); border: 1px solid var(--text-soft); }
</style>

<div class="page-head mb-3">
  <h2 class="mb-1">Ajukan Cuti / Sakit</h2>
  <div class="text-muted-soft">Sisa jatah cuti tahunan: <strong><?= (int)($me['jumlah_cuti'] ?? 0) ?> hari</strong></div>
</div>

<form method="post" enctype="multipart/form-data" class="card-soft" style="max-width:680px" id="form-cuti-create">
  <?= csrf_field() ?>

  <label class="form-label fw-semibold">Jenis Cuti</label>
  <select name="jenis" class="form-select mb-3 <?= isset($errors['jenis'])?'is-invalid':'' ?>" required>
    <option value="">— Pilih —</option>
    <?php foreach (['tahunan'=>'Cuti Tahunan','sakit'=>'Sakit','melahirkan'=>'Melahirkan','menikah'=>'Menikah'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= old('jenis')===$k?'selected':'' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <?php if(isset($errors['jenis'])): ?><div class="invalid-feedback d-block mb-3"><?= e($errors['jenis']) ?></div><?php endif; ?>

  <label class="form-label fw-semibold">Tanggal Cuti</label>
  <div class="cuti-cal">
    <div class="cuti-cal-head">
      <button type="button" class="btn btn-sm btn-light" id="cal-prev"><i class="bi bi-chevron-left"></i></button>
      <div class="fw-semibold" id="cal-title">&nbsp;</div>
      <button type="button" class="btn btn-sm btn-light" id="cal-next"><i class="bi bi-chevron-right"></i></button>
    </div>
    <div class="cuti-cal-grid" id="cal-grid"></div>
    <div class="cuti-cal-legend">
      <span><i class="cuti-cal-dot is-holiday"></i> Libur</span>
      <span><i class="cuti-cal-dot is-selected"></i> Terpilih</span>
      <span><i class="cuti-cal-dot is-blocked"></i> Sudah dipakai cuti lain</span>
    </div>
  </div>
  <div id="cal-hidden-inputs"><?php foreach ((array)old('tanggal', []) as $d): ?><input type="hidden" name="tanggal[]" value="<?= e($d) ?>"><?php endforeach; ?></div>
  <div class="invalid-feedback d-block mt-2 mb-2 <?= isset($errors['tanggal'])?'':'d-none' ?>" id="cal-error"><?= e($errors['tanggal'] ?? 'Pilih tanggal cuti terlebih dahulu di kalender.') ?></div>
  <button type="button" class="btn btn-sm btn-outline-secondary my-2" id="cal-clear">Hapus Pilihan</button>

  <label class="form-label fw-semibold">Alasan <span class="text-muted-soft">(umum, berlaku utk semua tanggal di atas)</span></label>
  <textarea name="alasan" rows="3" class="form-control mb-3 <?= isset($errors['alasan'])?'is-invalid':'' ?>" maxlength="1000" required placeholder="Jelaskan alasan pengajuan cuti…"><?= e(old('alasan')) ?></textarea>
  <?php if(isset($errors['alasan'])): ?><div class="invalid-feedback d-block mb-3"><?= e($errors['alasan']) ?></div><?php endif; ?>

  <label class="form-label fw-semibold">Lampiran <span class="text-muted-soft">(wajib untuk izin sakit, format PDF/JPG/PNG max 5 MB)</span></label>
  <input type="file" name="lampiran" class="form-control mb-3 <?= isset($errors['lampiran'])?'is-invalid':'' ?>" accept=".pdf,image/jpeg,image/png" />
  <?php if(isset($errors['lampiran'])): ?><div class="invalid-feedback d-block mb-3"><?= e($errors['lampiran']) ?></div><?php endif; ?>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/cuti') ?>" class="btn btn-light">Batal</a>
    <button class="btn btn-primary"><i class="bi bi-send"></i> Ajukan</button>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const holidays = <?= json_encode($holidayDates ?? []) ?>;
  const blocked  = <?= json_encode($blockedDates ?? []) ?>; // { 'YYYY-MM-DD': { jenis, status, note } }
  const initialSelected = <?= json_encode(array_values((array)old('tanggal', []))) ?>;

  const grid      = document.getElementById('cal-grid');
  const title     = document.getElementById('cal-title');
  const errBox    = document.getElementById('cal-error');
  const hiddenBox = document.getElementById('cal-hidden-inputs');
  const form      = document.getElementById('form-cuti-create');

  const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const dowNames   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
  const jenisLabel = { sakit:'Sakit', tahunan:'Cuti Tahunan', melahirkan:'Melahirkan', menikah:'Menikah', darurat:'Mendadak/Force Majeure' };

  const today = new Date();
  const selected = new Set(initialSelected);
  let viewYear, viewMonth;
  if (selected.size) {
    const first = Array.from(selected).sort()[0];
    const d = new Date(first + 'T00:00:00');
    viewYear = d.getFullYear();
    viewMonth = d.getMonth();
  } else {
    viewYear = today.getFullYear();
    viewMonth = today.getMonth();
  }

  function fmt(y, m, d) {
    return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
  }

  function render() {
    title.textContent = monthNames[viewMonth] + ' ' + viewYear;
    grid.innerHTML = '';

    dowNames.forEach(dn => {
      const el = document.createElement('div');
      el.className = 'cuti-cal-dow';
      el.textContent = dn;
      grid.appendChild(el);
    });

    const firstDow = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const todayStr = fmt(today.getFullYear(), today.getMonth(), today.getDate());

    for (let i = 0; i < firstDow; i++) {
      const pad = document.createElement('div');
      pad.className = 'cuti-cal-pad';
      grid.appendChild(pad);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = fmt(viewYear, viewMonth, d);
      const dow = new Date(viewYear, viewMonth, d).getDay();
      const blockedInfo = blocked[dateStr];

      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'cuti-cal-day';
      cell.textContent = String(d);
      cell.dataset.date = dateStr;

      if (dow === 0) cell.classList.add('is-sunday');
      if (holidays.includes(dateStr)) cell.classList.add('is-holiday');
      if (dateStr === todayStr) cell.classList.add('is-today');

      if (blockedInfo) {
        cell.classList.add('is-blocked');
        const label = jenisLabel[blockedInfo.jenis] || blockedInfo.jenis;
        const statusLabel = blockedInfo.status === 'pending' ? 'menunggu verifikasi' : 'disetujui';
        cell.title = 'Sudah ada cuti ' + label + ' (' + statusLabel + ')' + (blockedInfo.note ? ': ' + blockedInfo.note : '');
      } else if (selected.has(dateStr)) {
        cell.classList.add('is-selected');
      }

      cell.addEventListener('click', () => onDayClick(dateStr, blockedInfo));
      grid.appendChild(cell);
    }
  }

  function onDayClick(dateStr, blockedInfo) {
    if (blockedInfo) {
      const label = jenisLabel[blockedInfo.jenis] || blockedInfo.jenis;
      const statusLabel = blockedInfo.status === 'pending' ? 'menunggu verifikasi' : 'disetujui';
      return;
    }
    errBox.classList.add('d-none');
    if (selected.has(dateStr)) {
      selected.delete(dateStr);
    } else {
      selected.add(dateStr);
    }
    syncHiddenInputs();
    render();
  }

  function syncHiddenInputs() {
    hiddenBox.innerHTML = '';
    Array.from(selected).sort().forEach(d => {
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'tanggal[]';
      inp.value = d;
      hiddenBox.appendChild(inp);
    });
  }

  document.getElementById('cal-prev').addEventListener('click', () => {
    viewMonth--;
    if (viewMonth < 0) { viewMonth = 11; viewYear--; }
    render();
  });
  document.getElementById('cal-next').addEventListener('click', () => {
    viewMonth++;
    if (viewMonth > 11) { viewMonth = 0; viewYear++; }
    render();
  });
  document.getElementById('cal-clear').addEventListener('click', () => {
    selected.clear();
    syncHiddenInputs();
    render();
  });

  form.addEventListener('submit', (e) => {
    if (!selected.size) {
      e.preventDefault();
      errBox.classList.remove('d-none');
      grid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  render();
});
</script>
<?php unset($_SESSION['_old'], $_SESSION['_errors']); ?>
