/* ============================================================
   SiAbsen — absensi.js
   Live camera + GPS + face-api descriptor matching + AJAX submit.
   ============================================================ */
(function () {
  const cfg = (window.SIABSEN && window.SIABSEN.absensi) || {};
  const $ = (s) => document.querySelector(s);

  const video    = $('#cam');
  const overlay  = $('#overlay');
  const btn      = $('#btnSubmit');
  const hudFace  = $('#hudFace');
  const hudGps   = $('#hudGps');
  const metaLoc  = $('#metaLoc');
  const metaDist = $('#metaDist');
  const metaFace = $('#metaFace');
  if (!video || !btn) return;

  const officeLat   = parseFloat(btn.dataset.lat);
  const officeLng   = parseFloat(btn.dataset.lng);
  const radius      = parseInt(btn.dataset.radius, 10);
  const threshold   = parseFloat(btn.dataset.threshold) || 0.5;
  const hasFace     = btn.dataset.hasFace === '1';
  const type        = btn.dataset.type;

  let modelsReady = false;
  let lastDistance = null;
  let lastDescriptor = null;
  let gps = null;     // {lat,lng,accuracy}
  let gpsOk = false;
  let faceOk = !hasFace; // kalau user belum punya descriptor → skip face check

  window.SIABSEN = window.SIABSEN || {};

  function setHud(el, ok, text) {
    el.classList.remove('ok','bad','wait');
    el.classList.add(ok === true ? 'ok' : ok === false ? 'bad' : 'wait');
    el.innerHTML = text;
  }

  function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI/180;
    const dLng = (lng2 - lng1) * Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return 2 * R * Math.asin(Math.sqrt(a));
  }

  function refreshBtn() {
    btn.disabled = !(gpsOk && faceOk && modelsReady);
  }

  function parseTimeToMinutes(value) {
    if (!value) return null;
    const parts = String(value).split(':').map(Number);
    if (parts.length < 2 || parts.some(Number.isNaN)) return null;
    return parts[0] * 60 + parts[1];
  }

  function getShiftCandidates() {
    const shifts = Array.isArray(cfg.userShifts) ? cfg.userShifts : [];
    if (!shifts.length) return [];

    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();
    return shifts.filter((shift) => {
      const start = parseTimeToMinutes(shift.jam_masuk);
      const end = parseTimeToMinutes(shift.jam_keluar);
      if (start === null || end === null) return false;
      const isActive = currentMinutes >= start && currentMinutes <= end;
      const isNear = currentMinutes < start && (start - currentMinutes) <= 60;
      shift._isActive = isActive;
      shift._isNear = isNear;
      return isActive || isNear;
    });
  }

  function formatShiftLabel(shift) {
    const status = [];
    if (shift._isActive) status.push('Aktif');
    if (shift._isNear) status.push('Mendekati');
    const suffix = status.length ? ` (${status.join(' • ')})` : '';
    return `${shift.nama} (${String(shift.jam_masuk).slice(0, 5)}–${String(shift.jam_keluar).slice(0, 5)})${suffix}`;
  }

  // --- 1. GPS ---
  function startGps() {
    if (!('geolocation' in navigator)) {
      setHud(hudGps, false, '<i class="bi bi-x-circle"></i> GPS tidak didukung');
      return;
    }
    navigator.geolocation.watchPosition((pos) => {
      gps = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy };
      const dist = haversine(officeLat, officeLng, gps.lat, gps.lng);
      metaLoc.textContent  = gps.lat.toFixed(6) + ', ' + gps.lng.toFixed(6) + ' (±' + Math.round(gps.acc) + 'm)';
      metaDist.textContent = Math.round(dist) + ' m / max ' + radius + ' m';
      gpsOk = dist <= radius;
      setHud(hudGps, gpsOk,
        (gpsOk ? '<i class="bi bi-geo-alt-fill"></i> Dalam area' : '<i class="bi bi-geo-alt"></i> Di luar area')
        + ' (' + Math.round(dist) + 'm)'
      );
      refreshBtn();
    }, (err) => {
      setHud(hudGps, false, '<i class="bi bi-x-circle"></i> GPS ditolak/error');
    }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
  }

  // --- 2. Camera + face-api ---
  async function startCamera() {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
        audio: false
      });
      video.srcObject = stream;
      await new Promise((r) => video.onloadedmetadata = r);
    } catch (e) {
      setHud(hudFace, false, '<i class="bi bi-camera-video-off"></i> Kamera ditolak');
      Swal.fire('Kamera ditolak', 'Izinkan akses kamera lalu refresh halaman.', 'error');
      throw e;
    }
  }

  async function loadModels() {
    if (window.SIABSEN?.faceModelsReady) {
      modelsReady = true;
      setHud(hudFace, null, '<i class="bi bi-person-check-fill"></i> Model wajah siap');
      return;
    }

    setHud(hudFace, null, '<i class="bi bi-arrow-repeat"></i> Memuat model wajah…');
    try {
      if (window.SIABSEN?.faceModelsPromise) {
        await window.SIABSEN.faceModelsPromise;
      }

      const modelPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(cfg.modelsUrl),
        faceapi.nets.faceLandmark68Net.loadFromUri(cfg.modelsUrl),
        faceapi.nets.faceRecognitionNet.loadFromUri(cfg.modelsUrl)
      ]);
      window.SIABSEN.faceModelsPromise = modelPromise;
      await modelPromise;
      modelsReady = true;
      window.SIABSEN.faceModelsReady = true;
      setHud(hudFace, null, '<i class="bi bi-person-check-fill"></i> Model wajah siap');
    } catch (e) {
      setHud(hudFace, false, '<i class="bi bi-x-circle"></i> Gagal memuat model');
      throw e;
    }
  }

  async function detectLoop() {
    overlay.width  = video.videoWidth  || 640;
    overlay.height = video.videoHeight || 480;
    const ctx = overlay.getContext('2d');

    const tick = async () => {
      if (video.paused || video.ended) return setTimeout(tick, 400);
      const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
      let result = null;
      try {
        result = await faceapi
          .detectSingleFace(video, opts)
          .withFaceLandmarks()
          .withFaceDescriptor();
      } catch (e) { /* ignore single-frame errors */ }

      ctx.clearRect(0,0,overlay.width,overlay.height);
      if (result) {
        const box = result.detection.box;
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#10b981';
        ctx.strokeRect(box.x, box.y, box.width, box.height);
        lastDescriptor = Array.from(result.descriptor);

        if (hasFace && cfg.storedDesc && cfg.storedDesc.length) {
          const stored = cfg.storedDesc;
          let s = 0;
          for (let i = 0; i < stored.length; i++) {
            const d = stored[i] - lastDescriptor[i];
            s += d*d;
          }
          lastDistance = Math.sqrt(s);
          const score = Math.max(0, (1 - lastDistance) * 100);
          metaFace.textContent = score.toFixed(1) + '% (jarak ' + lastDistance.toFixed(3) + ')';
          faceOk = lastDistance <= threshold;
          setHud(hudFace, faceOk,
            (faceOk ? '<i class="bi bi-person-check-fill"></i> Wajah cocok'
                    : '<i class="bi bi-person-x-fill"></i> Wajah belum cocok')
            + ' (' + score.toFixed(0) + '%)'
          );
        } else {
          faceOk = true;
          metaFace.textContent = hasFace ? '—' : '(verifikasi dilewati)';
          setHud(hudFace, true, '<i class="bi bi-person-check-fill"></i> Wajah terdeteksi');
        }
      } else {
        if (hasFace) faceOk = false;
        setHud(hudFace, hasFace ? false : null,
          '<i class="bi bi-person-bounding-box"></i> Posisikan wajah Anda di tengah'
        );
      }
      refreshBtn();
      setTimeout(tick, 350);
    };
    tick();
  }

  function captureBase64() {
    const c = document.createElement('canvas');
    c.width  = video.videoWidth  || 640;
    c.height = video.videoHeight || 480;
    c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
    return c.toDataURL('image/jpeg', 0.85);
  }

  btn.addEventListener('click', async () => {
    if (btn.disabled) return;

    const foto = captureBase64();
    const now = new Date();
    const currentDate = now.toLocaleDateString('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' });
    const currentTime = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    let lateMinutes = 0;

    let selectedShift = null;
    if (type === 'masuk') {
      const candidates = getShiftCandidates();
      const shouldPrompt = Array.isArray(cfg.userShifts) && cfg.userShifts.length > 1 && candidates.length > 1;
      if (shouldPrompt) {
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        const optionsHtml = candidates.map((shift, idx) => {
          const badges = [];
          if (shift._isActive) badges.push('<span class="swal2-shift-option-badge active">Aktif</span>');
          if (shift._isNear) badges.push('<span class="swal2-shift-option-badge near">Mendekati</span>');
          const badgeHtml = badges.length ? `<div class="swal2-shift-option-meta"><span class="swal2-shift-option-time">${escapeHtml(String(shift.jam_masuk).slice(0, 5))}–${escapeHtml(String(shift.jam_keluar).slice(0, 5))}</span><span>${badges.join('')}</span></div>` : `<div class="swal2-shift-option-meta"><span class="swal2-shift-option-time">${escapeHtml(String(shift.jam_masuk).slice(0, 5))}–${escapeHtml(String(shift.jam_keluar).slice(0, 5))}</span></div>`;
          return `
            <label class="swal2-shift-option">
              <input type="radio" name="swalShiftSelection" value="${shift.id}" ${idx === 0 ? 'checked' : ''}>
              <span class="swal2-shift-option-body">
                <span class="swal2-shift-option-name">${escapeHtml(shift.nama)}</span>
                ${badgeHtml}
              </span>
            </label>`;
        }).join('');

        const result = await Swal.fire({
          title: 'Pilih shift absen masuk',
          html: `
            <div class="swal2-shift-picker">
              <div class="swal2-shift-header">
                <div class="swal2-shift-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                  <div class="swal2-shift-title">Pilih shift yang akan dipakai</div>
                  <div class="swal2-shift-subtitle">Shift yang muncul adalah shift yang sedang aktif atau akan dimulai dalam 1 jam ke depan.</div>
                </div>
              </div>
              <div class="swal2-shift-list">${optionsHtml}</div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Lanjutkan',
          cancelButtonText: 'Batal',
          customClass: {
            popup: 'swal2-absen-full',
            confirmButton: 'swal2-absen-confirm',
            cancelButton: 'swal2-absen-cancel'
          },
          buttonsStyling: false,
          preConfirm: () => {
            const selectedValue = Swal.getPopup().querySelector('input[name="swalShiftSelection"]:checked');
            if (!selectedValue) {
              Swal.showValidationMessage('Pilih satu shift terlebih dahulu.');
              return false;
            }
            return selectedValue.value;
          }
        });
        if (!result.isConfirmed || !result.value) return;
        selectedShift = candidates.find((shift) => String(shift.id) === String(result.value)) || null;
      } else {
        selectedShift = getShiftCandidates()[0] || null;
      }
    }

    const selectedShiftStart = selectedShift ? selectedShift.jam_masuk : cfg.shiftStart;
    const selectedShiftTolerance = selectedShift ? Number(selectedShift.toleransi_menit || 0) : (typeof cfg.shiftTolerance === 'number' ? cfg.shiftTolerance : 0);
    const isLate = type === 'masuk' && selectedShiftStart && typeof selectedShiftTolerance === 'number'
      ? (() => {
          const parts = String(selectedShiftStart).split(':').map(Number);
          if (parts.length < 2 || parts.some(Number.isNaN)) return false;
          const shiftDate = new Date();
          shiftDate.setHours(parts[0], parts[1] + selectedShiftTolerance, 0, 0);
          const diffMs = now.getTime() - shiftDate.getTime();
          lateMinutes = diffMs > 0 ? Math.ceil(diffMs / 60000) : 0;
          return diffMs > 0;
        })()
      : false;

    let reason = null;
    if (isLate) {
      const result = await Swal.fire({
        title: 'Terjadi keterlambatan',
        icon: 'warning',
        width: 'min(600px, calc(100vw - 24px))',
        padding: '1.2rem 1.3rem',
        html: `
          <div class="swal2-absen-grid">
            <div class="swal2-absen-card">
              <div class="swal2-absen-card-title">Rincian absensi</div>
              <div class="swal2-absen-meta">
                <div><span>Tanggal:</span> ${currentDate}</div>
                <div><span>Waktu absen masuk:</span> ${currentTime}</div>
                <div><span>Shift mulai:</span> ${selectedShiftStart || '—'}</div>
                <div><span>Menit keterlambatan:</span> <strong>${lateMinutes} menit</strong></div>
              </div>
            </div>
            <div class="swal2-absen-img">
              <img src="${foto}" alt="Foto terlambat" />
            </div>
            <textarea id="swalLateReason" class="swal2-absen-textarea" placeholder="Jelaskan alasan keterlambatan Anda..."></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan Absen',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        showCloseButton: false,
        customClass: {
          popup: 'swal2-absen-full',
          actions: 'swal2-absen-actions',
          confirmButton: 'swal2-absen-confirm',
          cancelButton: 'swal2-absen-cancel'
        },
        buttonsStyling: false,
        preConfirm: () => {
          const val = document.getElementById('swalLateReason')?.value.trim();
          if (!val) {
            Swal.showValidationMessage('Alasan terlambat harus diisi.');
          }
          return val;
        },
        didOpen: () => {
          const input = Swal.getPopup().querySelector('#swalLateReason');
          if (input) input.focus();
        }
      });
      if (!result.isConfirmed || !result.value) {
        return;
      }
      reason = result.value;
    }

    btn.disabled = true;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Mengirim…</span>';

    const fotoData = foto;
    const fd = new FormData();
    fd.append('_csrf', cfg.csrf);
    fd.append('type', type);
    fd.append('foto', fotoData);
    fd.append('lat', gps.lat);
    if (selectedShift && selectedShift.id) fd.append('shift_id', selectedShift.id);
    fd.append('lng', gps.lng);
    if (lastDistance !== null) fd.append('face_distance', lastDistance);
    if (lastDescriptor)        fd.append('descriptor', JSON.stringify(lastDescriptor));
    if (reason)                 fd.append('keterangan', reason);

    try {
      const res = await fetch(cfg.submitUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': cfg.csrf },
      });
      const text = await res.text();
      if (!res.ok) {
        throw new Error('HTTP ' + res.status + ': ' + text.slice(0, 300));
      }
      let json;
      try {
        json = JSON.parse(text);
      } catch (err) {
        throw new Error('Invalid JSON response: ' + text.slice(0, 300));
      }
      if (json.success) {
        await Swal.fire({ icon:'success', title:'Berhasil', text: json.message, timer: 2200, showConfirmButton:false });
        window.location = json.redirect || '/';
      } else {
        Swal.fire('Gagal', json.message || 'Terjadi kesalahan', 'error');
        btn.disabled = false;
        btn.innerHTML = oldHtml;
      }
    } catch (e) {
      console.error('Absensi submit error:', e);
      Swal.fire('Gagal', 'Tidak bisa menghubungi server: ' + e.message, 'error');
      btn.disabled = false;
      btn.innerHTML = oldHtml;
    }
  });

  (async () => {
    startGps();
    await startCamera();
    await loadModels();
    detectLoop();
  })().catch(console.error);
})();
