/**
 * FaceCapture - browser-side face descriptor extractor (face-api.js)
 * Dipakai di form Karyawan create/edit. Menyimpan descriptor 128-dim sebagai JSON
 * ke hidden input #face_descriptor.
 *
 * API: FaceCapture.init({ modelsUrl: '...', requireDescriptor: true|false });
 */
(function (global) {
  const FaceCapture = {
    cfg: { modelsUrl: '/assets/models', requireDescriptor: false },
    modelsLoaded: false,
    descriptor: null,

    async init(opts) {
      Object.assign(this.cfg, opts || {});

      const fileInput  = document.getElementById('foto_profile');
      const previewImg = document.getElementById('face-preview');
      const overlay    = document.getElementById('face-overlay');
      const status     = document.getElementById('face-status');
      const btnExtract = document.getElementById('btn-extract');
      const hidden     = document.getElementById('face_descriptor');
      const form       = document.getElementById('form-karyawan');
      const btnSubmit  = document.getElementById('btn-submit');

      if (!fileInput || !previewImg || !btnExtract || !hidden) return;

      this.setStatus(status, 'info', 'Memuat model face-api.js... (~7 MB, sekali saja)');
      try {
        await this.loadModels();
        this.setStatus(status, 'success', 'Model siap. Pilih foto, lalu klik "Ekstrak Face Descriptor".');
        btnExtract.disabled = false;
      } catch (e) {
        this.setStatus(status, 'danger', 'Gagal memuat model: ' + e.message);
        return;
      }

      fileInput.addEventListener('change', () => {
        const f = fileInput.files[0];
        if (!f) return;
        previewImg.src = URL.createObjectURL(f);
        previewImg.onload = () => {
          overlay.width  = previewImg.clientWidth;
          overlay.height = previewImg.clientHeight;
          overlay.getContext('2d').clearRect(0,0,overlay.width,overlay.height);
        };
        this.descriptor = null;
        hidden.value    = '';
      });

      btnExtract.addEventListener('click', async () => {
        if (!previewImg.src) {
          this.setStatus(status, 'warning', 'Pilih foto dulu.');
          return;
        }
        btnExtract.disabled = true;
        this.setStatus(status, 'info', 'Mendeteksi wajah & mengekstrak descriptor...');
        try {
          const det = await faceapi
            .detectSingleFace(previewImg, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

          if (!det) {
            this.setStatus(status, 'danger', 'Wajah tidak terdeteksi. Gunakan foto potrait dengan wajah jelas.');
            btnExtract.disabled = false;
            return;
          }

          this.descriptor = Array.from(det.descriptor);
          hidden.value = JSON.stringify(this.descriptor);

          // gambar bounding box
          const box = det.detection.box;
          const sx = previewImg.clientWidth  / previewImg.naturalWidth;
          const sy = previewImg.clientHeight / previewImg.naturalHeight;
          const ctx = overlay.getContext('2d');
          ctx.clearRect(0, 0, overlay.width, overlay.height);
          ctx.strokeStyle = '#22c55e';
          ctx.lineWidth = 3;
          ctx.strokeRect(box.x * sx, box.y * sy, box.width * sx, box.height * sy);

          this.setStatus(status, 'success', '✓ Descriptor 128-dim berhasil diekstrak. Anda bisa menyimpan karyawan sekarang.');
        } catch (e) {
          this.setStatus(status, 'danger', 'Gagal: ' + e.message);
        } finally {
          btnExtract.disabled = false;
        }
      });

      // Cegah submit kalau wajib descriptor & kosong
      if (form && this.cfg.requireDescriptor) {
        form.addEventListener('submit', (e) => {
          if (!hidden.value) {
            e.preventDefault();
            this.setStatus(status, 'danger', 'Wajib ekstrak face descriptor sebelum simpan. Klik tombol "Ekstrak Face Descriptor".');
            window.scrollTo({ top: status.offsetTop - 80, behavior: 'smooth' });
          }
        });
      }
    },

    async loadModels() {
      if (this.modelsLoaded) return;
      const url = this.cfg.modelsUrl;
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(url),
        faceapi.nets.faceLandmark68Net.loadFromUri(url),
        faceapi.nets.faceRecognitionNet.loadFromUri(url),
      ]);
      this.modelsLoaded = true;
    },

    setStatus(el, type, msg) {
      if (!el) return;
      el.className = 'alert alert-' + type + ' mt-3 mb-0 small';
      el.textContent = msg;
      el.classList.remove('d-none');
    },
  };

  global.FaceCapture = FaceCapture;
})(window);
