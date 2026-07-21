# 📋 Stage Checklist — Sistem Absensi Karyawan Sekolah

✅ = selesai · 🟡 = on progress · ⬜ = belum

## ✅ Stage 1 — Foundation `(SELESAI)`
Front controller, Core MVC (App/Router/Database/Model/Controller/View/Session/Csrf/Validator/Request/Response), Auth (login NIY+password, Remember Me, CSRF, regenerate, logout), 3 dashboard skeleton, design system CSS token-based, vendor lokal (Bootstrap 5, Bootstrap Icons, SweetAlert2), DB SQL lengkap (8 tabel + 10 user dummy), reset_password.php, README, ERD, FLOWCHART, dummy_accounts.txt → `absensi-sekolah-stage1.zip`

## ✅ Stage 2 — Master Data + Pengumuman `(SELESAI)`
Karyawan CRUD (DataTables + filter + foto + face descriptor extract via face-api.js + reset password), Shift CRUD (modal + proteksi hapus), Pengumuman CRUD + toggle publish + banner. Vendor: DataTables 2.1.8, face-api 1.7.13 + 3 model file (~7 MB). Validation lengkap, upload mime/size/UUID rename, XSS escape → `absensi-sekolah-stage2.zip`

## ✅ Stage 3 — Absensi (selfie + GPS + face match) `(SELESAI)`
- AbsensiController: form (mobile-first), submit AJAX masuk/keluar, riwayat (filter bulan)
- Helper baru: `face.php` (decode + Euclidean + score), `upload.php` (save base64 + Haversine)
- `assets/js/absensi.js`: getUserMedia + watchPosition + face-api real-time bounding box & match
- Server-side validation: anti double-absen, GPS radius (Haversine), jam keluar > masuk, status auto hadir/telat (vs shift+toleransi), re-verify face descriptor, MIME finfo
- View riwayat: thumbnail, status badge, link Maps, skor face, summary card per status, empty state
- Vendor tambahan: Chart.js 4.4.4 → `absensi-sekolah-stage3.zip`

## ✅ Stage 4 — Cuti, Laporan, Polish `(SELESAI - FINAL)`
- **CutiController**: `/cuti` riwayat personal + sisa cuti, `/cuti/create` form (jenis/tanggal/alasan/lampiran), upload surat (PDF/JPG/PNG max 5 MB) wajib utk Sakit, validasi tanggal selesai ≥ mulai
- **VerifikasiController**: `/verifikasi-cuti` filter status, modal approve/reject + catatan. HRD lihat semua, Kepsek hanya pengajuan dari Guru. Logic auto: non-Sakit yg di-approve → kurangi `users.jumlah_cuti` (selisih hari + 1)
- **LaporanController**:
  - General (HRD/Kepsek): rekap per karyawan, kolom hadir/telat/izin/sakit/alpha/total, total footer, filter periode
  - Personal (pegawai): summary card + Chart.js (line kehadiran harian + doughnut distribusi status)
  - **Export Excel**: HTML table + `Content-Type: application/vnd.ms-excel` (tanpa PhpSpreadsheet, ringan, tetap bisa dibuka Excel)
- **Dashboard live (Chart.js)**:
  - HRD: total karyawan, hadir/telat hari ini, cuti pending, **bar stacked trend 7 hari**
  - Kepsek: hadir/telat/izin-sakit hari ini, **line trend 7 hari**, status pribadi
  - Pegawai (mobile): **streak hadir, sisa cuti, jam kerja minggu ini**, action grid 4 tombol (Absen, Riwayat, Cuti, Laporan)
- Polish: gradient cards, alert-soft, badge subtle, empty states, loading state pada absensi.js (SweetAlert), bottom-nav final, status badge re-usable, dark-mode-friendly tokens
- **Final QA checklist** + update README → `absensi-sekolah-final.zip`

## 🧪 Smoke Test Stage 4 (jalankan di Laragon/XAMPP)
1. Login pegawai → menu **Cuti** → **Ajukan Baru** → pilih *Tahunan*, set tanggal, tulis alasan → kirim → status `pending`.
2. Login HRD → **Verifikasi Cuti** → klik **Setujui** → modal catatan → simpan → status berubah `approved` & `users.jumlah_cuti` pegawai berkurang.
3. Login Kepsek → **Verifikasi Cuti** → hanya pengajuan dari role **Guru** yang muncul.
4. HRD → **Laporan** → pilih bulan/tahun → tabel rekap muncul → klik **Excel** → file `.xls` ter-download dan bisa dibuka di Excel/LibreOffice Calc.
5. Pegawai → **Laporan** → grafik line + doughnut tampil sesuai data bulan.
6. Cek dashboard HRD → grafik bar stacked 7 hari muncul; dashboard pegawai → streak/sisa cuti/jam minggu update.

## 📦 Output Final
`absensi-sekolah-final.zip` (full project, siap import ke Laragon/XAMPP)
