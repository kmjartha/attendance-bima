# SiAbsen — Sistem Absensi Sekolah

Aplikasi absensi karyawan/guru sekolah dengan face recognition, GPS validation,
manajemen cuti, dan laporan. PHP MVC custom (tanpa framework eksternal —
autoload & router ditulis sendiri di `app-core/app/core`).

## 📁 Struktur Folder

```
public_html/                ← DOCUMENT ROOT Hostinger (fixed, tidak bisa diubah)
├── index.php                front controller — TITIK MASUK satu-satunya
├── .htaccess                 routing + blokir akses ke app-core/
├── sw-face-models.js
├── assets/                   css, js, vendor (bootstrap, chart.js, face-api), model AI
├── uploads/                  foto profil, foto absensi, dokumen cuti, banner pengumuman
└── app-core/                 SOURCE CODE — diblokir total dari akses browser
    ├── .htaccess              `Require all denied` — wajib ada, jangan dihapus
    ├── .env                   kredensial aktual (TIDAK di-commit ke git)
    ├── .env.example            template kredensial (aman di-commit)
    ├── app/
    │   ├── controllers/
    │   ├── core/               App, Router, Database, Model, View, Session, dst
    │   ├── helpers/             auth, url, format, upload, face
    │   ├── middleware/
    │   ├── models/
    │   └── views/
    ├── config/
    │   ├── app.php
    │   ├── database.php
    │   └── env.php
    ├── database/
    │   ├── migrations/
    │   ├── seeds/
    │   └── *.sql               dump backup — TIDAK di-commit
    ├── routes/
    │   └── web.php
    └── storage/                 log & cache
```

### Kenapa struktur seperti ini?

Idealnya source code (`app`, `config`, `database`) ditaruh **di luar** document
root sama sekali, supaya tidak bisa diakses lewat browser apapun yang terjadi.
Tapi paket **Hostinger Web/Cloud hosting tidak mengizinkan custom document
root** — domain selalu mengarah ke `public_html`, titik, tidak bisa diarahkan
ke subfolder seperti `public_html/public`.

Karena itu, source code tetap ditaruh **di dalam** `public_html`, tapi di
subfolder `app-core/` yang diblokir total lewat `.htaccess` (`Require all
denied`). Selama Apache/LiteSpeed Hostinger membaca `.htaccess` (defaultnya
ya), folder ini tidak bisa diakses dari luar sama sekali — baik file PHP-nya,
`.env`, maupun dump SQL — walau seseorang tahu URL persisnya.

Ada 2 lapis proteksi:
1. `.htaccess` di dalam `app-core/` sendiri (`Require all denied`)
2. `.htaccess` di `public_html/` root juga punya rule tambahan yang menolak
   request ke `app-core/*` sebelum sempat masuk ke folder tersebut

**Jangan pernah menghapus kedua `.htaccess` ini.**

## 🚀 Setup di Hostinger (Production)

1. **Upload semua isi folder ini** (bukan foldernya sendiri, tapi ISI di
   dalamnya) ke `public_html` Hostinger lewat File Manager atau FTP/SSH.
   Pastikan `index.php`, `.htaccess`, `assets/`, `uploads/`, `app-core/`
   semuanya langsung di root `public_html`.
2. **Cek `.htaccess` ter-upload dengan benar** — kadang FTP client
   menyembunyikan file yang diawali titik. Aktifkan "show hidden files"
   di FTP client / File Manager.
3. **Buat database baru** di hPanel Hostinger → MySQL Databases.
4. **Import database**: gunakan phpMyAdmin Hostinger, import file
   `absensi_prod_database.sql` (disediakan terpisah, tidak ikut folder ini).
5. **Edit file `app-core/.env`** lewat File Manager, isi kredensial sesuai
   database yang baru dibuat:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-baru-anda.com

   DB_HOST=localhost
   DB_NAME=nama_database_baru
   DB_USER=user_database_baru
   DB_PASS=password_database_baru
   ```
6. **Set permission** folder `uploads/*` dan `app-core/storage/*` agar bisa
   ditulis web server (biasanya `755`, kadang `775` tergantung kebijakan
   Hostinger).
7. **Tes proteksi `app-core/`**: buka langsung di browser, misalnya
   `https://domain-anda.com/app-core/.env` atau
   `https://domain-anda.com/app-core/config/database.php` — harus muncul
   error **403 Forbidden**. Kalau malah ke-download atau tampil isinya,
   **STOP, jangan lanjut pakai**, berarti `.htaccess` tidak aktif (cek apakah
   `mod_rewrite`/`AllowOverride` aktif di paket hosting Anda, atau hubungi
   support Hostinger).
8. Buka domain Anda → harus muncul halaman login.

> ⚠️ File `.env` yang lama (sebelum migrasi) berisi kredensial database
> production sebelumnya dalam plaintext. Pastikan generate user/password
> baru di hosting baru — **jangan reuse password lama**.

## 💻 Setup Local Development

Untuk local, Anda **bisa** pakai struktur ideal (document root mengarah ke
folder terpisah), karena di laptop sendiri bebas atur. Rekomendasi:

```bash
git clone -b dev <url-repo-baru> absensi-sekolah
cd absensi-sekolah
cp app-core/.env.example app-core/.env
# edit app-core/.env sesuaikan database local
php -S localhost:8000
```

Karena `index.php` ada di root repo (sama seperti yang dipakai di
`public_html`), `php -S localhost:8000` tanpa `-t` sudah otomatis benar.

Buat database local, import dump SQL terbaru Anda ke database local tersebut.

## 🌿 Alur Git: main / dev / local

```
main   → branch production, mencerminkan kondisi di Hostinger live
dev    → branch staging/development bersama tim
local  → opsional, kerja pribadi di laptop / feature branch
```

Langkah migrasi:

1. **Push struktur baru ini ke repo GitHub baru** sebagai `main`:
   ```bash
   cd public_html      # folder ini, yang sudah di-git init
   git remote add origin <url-repo-baru>
   git push -u origin main
   ```
2. **Buat branch `dev`**:
   ```bash
   git checkout -b dev
   git push -u origin dev
   ```
3. **Clone ke laptop/local**:
   ```bash
   git clone -b dev <url-repo-baru> absensi-sekolah
   ```
4. Folder `dev/` yang sebelumnya nyangkut secara fisik di server lama
   (duplikat aplikasi lengkap) **tidak diperlukan lagi** — cukup pakai
   branch git `dev`, jangan duplikat folder fisik lagi di server yang sama.

## 🔒 Catatan Keamanan

- `app-core/.env` **tidak boleh** pernah di-commit ke git (sudah di
  `.gitignore`) — dan secara fisik diblokir browser via `.htaccess`.
- Dump SQL (`app-core/database/*.sql`) tidak di-commit karena berisi data
  pribadi karyawan (foto, dokumen cuti) — simpan backup di tempat aman
  terpisah.
- `uploads/` berisi foto wajah & dokumen pribadi karyawan — `Options
  -Indexes` aktif di `.htaccess` supaya listing folder tidak terbuka publik.
- **Wajib** lakukan langkah tes 403 di atas (poin 7, bagian setup) setiap
  kali pindah hosting atau setelah ada perubahan konfigurasi server.
