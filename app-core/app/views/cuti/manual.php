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
.cuti-cal-legend .cuti-cal-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
.cuti-cal-legend .cuti-cal-dot.is-holiday { background: var(--warning); }
.cuti-cal-legend .cuti-cal-dot.is-selected { background: var(--primary); }
.cuti-cal-legend .cuti-cal-dot.is-blocked { background: var(--surface-2); border: 1px solid var(--text-soft); }
</style>

<div class="page-head mb-3">
  <h2 class="mb-1">Input Cuti Manual</h2>
  <div class="text-muted-soft">Lihat riwayat cuti karyawan &amp; input cuti baru langsung — cocok utk kejadian mendadak/force majeure.</div>
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

  <div class="card-soft mb-3" style="max-width:680px">
    <h3 class="h6 mb-3">Riwayat Cuti — <?= e($selectedUser['nama']) ?></h3>
    <?php if (empty($existingLeaves)): ?>
      <div class="text-muted-soft">Belum ada riwayat cuti.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Jenis</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($existingLeaves as $r): ?>
            <tr>
              <td class="text-capitalize"><?= e($r['jenis']) ?></td>
              <td><?= format_leave_dates($r['tanggal_list'] ?? null, $r['tanggal_mulai'], $r['tanggal_selesai']) ?></td>
              <td><?= status_badge($r['status']) ?></td>
              <td class="text-end">
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                  <a href="<?= url('/cuti/'.$r['id'].'/edit') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square"></i> Ubah
                  </a>
                  <form method="post" action="<?= url('/cuti/'.$r['id'].'/delete') ?>" class="d-inline-block">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e('/cuti/manual?user_id='.$selectedUserId) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash3"></i> Hapus
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="alert alert-info d-flex gap-2 align-items-start" role="alert" style="max-width:680px">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div>
      Klik tanggal di kalender utk memilih — boleh tidak berurutan. Cuti baru akan langsung berstatus <strong>disetujui</strong>, tidak perlu menunggu verifikasi.
      <div class="mt-2 fw-semibold" id="cal-selected-info">&nbsp;</div>
    </div>
  </div>

  <form method="post" action="<?= url('/cuti/manual') ?>" enctype="multipart/form-data" class="card-soft" style="max-width:680px" id="form-cuti-manual">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">

    <label class="form-label fw-semibold">Jenis Cuti</label>
    <select name="jenis" class="form-select mb-3 <?= isset($errors['jenis'])?'is-invalid':'' ?>" required>
      <option value="">— Pilih —</option>
      <?php foreach (['darurat'=>'Mendadak / Force Majeure','tahunan'=>'Cuti Tahunan','sakit'=>'Sakit','melahirkan'=>'Melahirkan','menikah'=>'Menikah'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= old('jenis')===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>

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

    <div id="cal-date-notes" class="mb-1"></div>

    <label class="form-label fw-semibold">Keterangan <span class="text-muted-soft">(umum, opsional)</span></label>
    <textarea name="alasan" rows="3" class="form-control mb-3" maxlength="1000" placeholder="Contoh: Force majeure, keluarga sakit mendadak, dll. Boleh dikosongkan."><?= e(old('alasan')) ?></textarea>

    <label class="form-label fw-semibold">Surat / Lampiran <span class="text-muted-soft">(opsional — boleh menyusul, PDF/JPG/PNG max 5 MB)</span></label>
    <input type="file" name="file_surat" class="form-control mb-3" accept="application/pdf,image/jpeg,image/png">

    <div class="d-flex gap-2 justify-content-end">
      <a href="<?= url('/cuti/manual') ?>" class="btn btn-light">Batal</a>
      <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Simpan &amp; Setujui</button>
    </div>
  </form>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const holidays = <?= json_encode($holidayDates ?? []) ?>;
    const blocked  = <?= json_encode($blockedDates ?? []) ?>;
    const initialSelected = <?= json_encode(array_values((array)old('tanggal', []))) ?>;

    const grid      = document.getElementById('cal-grid');
    const title     = document.getElementById('cal-title');
    const info      = document.getElementById('cal-selected-info');
    const errBox    = document.getElementById('cal-error');
    const hiddenBox = document.getElementById('cal-hidden-inputs');
    const notesBox  = document.getElementById('cal-date-notes');
    const form      = document.getElementById('form-cuti-manual');

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dowNames   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const jenisLabel = { sakit:'Sakit', tahunan:'Cuti Tahunan', melahirkan:'Melahirkan', menikah:'Menikah', darurat:'Mendadak/Force Majeure' };

    const today = new Date();
    const selected = new Set(initialSelected);
    const dateNotes = <?= json_encode((array)old('catatan_tanggal', [])) ?>;

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
        info.textContent = 'Tanggal ' + dateStr + ' sudah dipakai cuti ' + label + ' (' + statusLabel + ') — tidak bisa dipilih lagi.';
        return;
      }
      errBox.classList.add('d-none');
      if (selected.has(dateStr)) {
        selected.delete(dateStr);
        delete dateNotes[dateStr];
      } else {
        selected.add(dateStr);
      }
      syncHiddenInputs();
      renderDateNotes();
      updateInfo();
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

    function renderDateNotes() {
      notesBox.innerHTML = '';
      const sortedDates = Array.from(selected).sort();
      if (!sortedDates.length) return;

      const header = document.createElement('label');
      header.className = 'form-label fw-semibold mb-1';
      header.textContent = 'Keterangan per tanggal (opsional)';
      notesBox.appendChild(header);

      const hint = document.createElement('div');
      hint.className = 'text-muted-soft mb-2';
      hint.style.fontSize = '.78rem';
      hint.textContent = 'Kosongkan kalau alasannya sama semua — pakai kolom Keterangan umum di bawah saja.';
      notesBox.appendChild(hint);

      const opt = { weekday: 'short', day: 'numeric', month: 'long' };
      sortedDates.forEach(d => {
        const label = new Date(d + 'T00:00:00').toLocaleDateString('id-ID', opt);
        const row = document.createElement('div');
        row.className = 'input-group input-group-sm mb-2';

        const span = document.createElement('span');
        span.className = 'input-group-text';
        span.style.minWidth = '150px';
        span.textContent = label;

        const inp = document.createElement('input');
        inp.type = 'text';
        inp.className = 'form-control';
        inp.maxLength = 255;
        inp.name = 'catatan_tanggal[' + d + ']';
        inp.value = dateNotes[d] || '';
        inp.placeholder = 'khusus tanggal ini (opsional)';
        inp.addEventListener('input', () => { dateNotes[d] = inp.value; });

        row.appendChild(span);
        row.appendChild(inp);
        notesBox.appendChild(row);
      });
    }

    function updateInfo() {
      if (!selected.size) {
        info.textContent = 'Belum ada tanggal dipilih.';
        return;
      }
      const opt = { day: 'numeric', month: 'long', year: 'numeric' };
      const labels = Array.from(selected).sort().map(d => new Date(d + 'T00:00:00').toLocaleDateString('id-ID', opt));
      info.textContent = 'Dipilih (' + selected.size + ' hari): ' + labels.join(', ');
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
      for (const k in dateNotes) delete dateNotes[k];
      syncHiddenInputs();
      renderDateNotes();
      updateInfo();
      render();
    });

    form.addEventListener('submit', (e) => {
      if (!selected.size) {
        e.preventDefault();
        errBox.classList.remove('d-none');
        grid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    updateInfo();
    renderDateNotes();
    render();
  });
  </script>

<?php endif; ?>
<?php unset($_SESSION['_old'], $_SESSION['_errors']); ?>
