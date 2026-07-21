/* SiAbsen — global JS */
(function(){
  // CSRF token untuk semua AJAX
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta && window.fetch) {
    const orig = window.fetch;
    window.fetch = function(input, init = {}) {
      init.headers = init.headers || {};
      if (!(init.headers instanceof Headers)) {
        init.headers['X-CSRF-Token']    = meta.getAttribute('content');
        init.headers['X-Requested-With']= 'XMLHttpRequest';
      }
      return orig(input, init);
    };
  }

  // Sidebar toggle (mobile/tablet)
  const toggle = document.querySelector('[data-sidebar-toggle]');
  const sb     = document.querySelector('.sidebar');
  if (toggle && sb) {
    toggle.addEventListener('click', () => sb.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth < 992 && sb.classList.contains('open')
          && !sb.contains(e.target) && !toggle.contains(e.target)) {
        sb.classList.remove('open');
      }
    });
  }

  // Live clock di mobile dashboard
  const clock = document.querySelector('[data-clock]');
  if (clock) {
    const tick = () => {
      const d = new Date();
      const pad = n => n.toString().padStart(2, '0');
      clock.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    };
    tick(); setInterval(tick, 1000);
  }
})();

// Password show/hide toggle
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.password-toggle-text');
  if (!btn) return;
  const field = btn.closest('.password-field');
  if (!field) return;
  const input = field.querySelector('[data-password-toggle]');
  if (!input) return;

  const isVisible = input.type === 'text';
  input.type = isVisible ? 'password' : 'text';
  btn.textContent = isVisible ? 'Tampilkan password' : 'Sembunyikan password';
  btn.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
});

// SweetAlert2 confirm untuk semua form .form-confirm-delete
document.addEventListener('submit', (e) => {
  const f = e.target.closest('.form-confirm-delete');
  if (!f || f.dataset.confirmed === '1') return;
  e.preventDefault();
  if (!window.Swal) { if (confirm('Hapus data ini?')) { f.dataset.confirmed='1'; f.submit(); } return; }
  Swal.fire({
    title: 'Yakin hapus?', text: 'Tindakan ini tidak bisa dibatalkan.',
    icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) { f.dataset.confirmed='1'; f.submit(); } });
});
