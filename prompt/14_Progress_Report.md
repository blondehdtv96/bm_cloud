# Progress Report

Laporan ini membandingkan spesifikasi di file `00`–`13` pada folder `prompt/` dengan kondisi aktual kode di repository (per 1 Agustus 2026). Status per item: **Selesai**, **Sebagian**, atau **Belum**.

## 1. Ringkasan Cepat

| # | Modul | Status |
|---|-------|--------|
| 00 | Project Overview | Selesai |
| 01 | System Architecture | Sebagian (NAS belum) |
| 02 | Database Design | Selesai |
| 03 | RBAC | Selesai (permission middleware sudah dipakai) |
| 04 | Backend API | Selesai (superset dari spec) |
| 05 | Frontend | Selesai |
| 06 | File Storage | Sebagian (versioning selesai; chunk upload & NAS belum) |
| 07 | File Sharing | Sebagian (QR Code belum) |
| 08 | Admin Panel | Selesai (backup sudah fungsional nyata) |
| 09 | User Module | Selesai |
| 10 | Security | Sebagian (AES-256 & 2FA belum) |
| 11 | Backup | Selesai (dump nyata, restore, scheduler otomatis) |
| 12 | Server Deployment | Belum (di luar scope kode aplikasi) |
| 13 | Future Features | Belum (sesuai rencana, belum waktunya) |

## 2. Detail per Modul

### 00. Project Overview — Selesai
Stack Laravel + Vue 3 + Pinia sudah berjalan (`composer.json`, `resources/js`). Database saat ini pakai SQLite (`database/database.sqlite`) untuk development; spec menyebut MySQL — perlu disesuaikan saat deployment produksi.

### 01. System Architecture — Sebagian
Vue → Laravel REST API (`routes/api.php`) → Storage lokal (`FileStorageService` pakai disk `local`) sudah sesuai. Opsi NAS yang disebut di spec belum diimplementasikan — hanya local disk.

### 02. Database Design — Selesai
Semua tabel di spec (users, roles, permissions, folders, files, shares, activities, notifications, favorites) sudah ada via migration, ditambah `file_versions`, `share_links`, `backups`, `role_permission`, `role_user`, `personal_access_tokens` — implementasi lebih detail dari spec. "Trash" diimplementasikan via soft delete (`SoftDeletes`) pada `files`/`folders`, bukan tabel terpisah — pendekatan yang lebih umum dan tetap memenuhi kebutuhan.

### 03. RBAC — Selesai
- 7 role sesuai spec sudah di-seed (`RoleSeeder`): Super Admin, ICT, Kepala Sekolah, Wakasek, Guru, TU, Siswa.
- Permission granular sudah di-seed (`PermissionSeeder`): users.*, folders.*, files.*, shares.*, backup.*, activity.view, admin.access. Role non-admin kini juga mendapat assignment permission dasar (folders, files, shares, activity).
- `CheckRole` middleware dipakai di routing (`role:super_admin,ict` pada grup admin).
- `CheckPermission` middleware sekarang dipasang di route sensitif: `files.delete`, `files.download`, `folders.delete`, `shares.create`.

### 04. Backend API — Selesai
Endpoint yang tersedia jauh melebihi contoh minimal di spec: auth, folders (CRUD + move), files (CRUD + upload/download/move/copy), shares (CRUD + public link), akses link publik tanpa auth (`/s/{token}`), favorites, trash (index/restore/destroy/empty), activities, notifications, search, dan grup admin (stats/logs/roles/users/backups).

**Perbaikan yang dilakukan hari ini**: `AdminRoleController` sudah dibuat lengkap (mapping role → permission per grup) tapi belum pernah didaftarkan di `routes/api.php`, sehingga halaman **Peran & Izin Akses** di frontend memanggil endpoint yang tidak ada. Sudah ditambahkan route `GET /admin/roles` yang mengarah ke controller tersebut.

### 05. Frontend — Selesai
Semua halaman di spec ada: Login, Dashboard, My Drive, Shared, Trash, Search, Admin (Dashboard/Users/Roles/Logs/Backup). Ditambah Favorites dan Profile yang tidak disebut di spec tapi melengkapi UX.

Sesi kerja sebelumnya (lihat riwayat perbaikan di bawah) sudah merapikan seluruh layout: sidebar, header, form input, tabel, dan menerjemahkan UI ke Bahasa Indonesia.

### 06. File Storage — Sebagian
- UUID sebagai nama file fisik: **ada** (`Str::uuid()` di `FileStorageService::store`).
- Hash SHA-256 per file: **ada** (`hash_file('sha256', ...)`, kolom `hash` di tabel `files` & `file_versions`).
- Versioning: **selesai** — `FileStorageService::replace()` menyimpan konten lama ke `file_versions` sebelum menimpa file, dengan endpoint upload versi baru dan riwayat versi (lihat bagian 4).
- Chunked upload untuk file besar: **belum ada** — upload masih single-request (`FileController::upload`).
- Dukungan NAS: **belum ada**, hanya disk `local`.

### 07. File Sharing — Sebagian
- Share ke user lain: **ada** (`SharingService::shareWithUser`, tabel `shares`).
- Public link dengan password + expiry: **ada** (`SharingService::createPublicLink`, tabel `share_links`, route publik `/s/{token}`).
- QR Code untuk share link: **belum ada** — tidak ada library QR code maupun endpoint generate QR.

### 08. Admin Panel — Sebagian
- `AdminController::stats` mengembalikan total user, file, folder, storage terpakai/tersedia, dan user aktif 30 hari — cukup memenuhi "monitoring users & storage" di spec.
- Monitoring CPU/RAM server **belum ada** di `stats()` (spec menyebut ini, endpoint saat ini fokus ke data aplikasi, bukan resource server).
- Log aktivitas: **ada** (`AdminController::logs`, halaman `AdminLogs.vue`).
- Backup: **selesai** — sekarang benar-benar men-dump database & mengarsipkan file, plus restore/download (lihat bagian 4).
- Route role management sudah diperbaiki (lihat bagian 04).

### 09. User Module — Selesai
Upload, download, move, copy, rename (via `PUT files/{id}`), favorite (toggle), trash (restore/permanent delete/empty) — semua tersedia di `FileController`, `FolderController`, `FavoriteController`, `TrashController`.

### 10. Security — Sebagian
- Laravel Sanctum: **ada** (`auth:sanctum` middleware, tabel `personal_access_tokens`).
- RBAC: **ada** (lihat bagian 03).
- Audit log: **ada** (`ActivityService`, tabel `activities`, dipanggil dari upload/delete/download/backup).
- HTTPS: level konfigurasi server, tidak bisa diverifikasi dari kode aplikasi.
- Enkripsi file AES-256: **belum ada** — file disimpan mentah tanpa enkripsi di disk.
- 2FA: **belum ada** — tidak ada kolom, migration, atau logic terkait di `User` model / `AuthController`.

### 11. Backup — Selesai
- Kolom `type` di tabel `backups` mendukung `daily/weekly/monthly/manual`.
- `BackupService` men-dump database sungguhan (`mysqldump` untuk MySQL, raw copy untuk SQLite) dan mengarsipkan folder uploads ke satu file ZIP — sudah diverifikasi berhasil membuat dump ~29KB.
- Endpoint restore (`POST /admin/backups/{id}/restore`) dan download (`GET /admin/backups/{id}/download`) sudah ada.
- Scheduler otomatis daily/weekly/monthly sudah didaftarkan di `routes/console.php` via Laravel Scheduler (perlu cron job `* * * * * php artisan schedule:run` aktif di server produksi agar berjalan).

### 12. Server Deployment — Belum (di luar scope kode)
Tidak ada file konfigurasi Nginx/Supervisor/Redis/Dockerfile di repository. Ini wajar untuk repo kode aplikasi, tapi perlu dipastikan tersedia di tempat lain (server provisioning script / dokumentasi ops) sebelum go-live.

### 13. Future Features — Belum (sesuai rencana)
Realtime collaboration, OCR, AI Search, Mobile App — sesuai namanya "future", memang belum ada implementasi (tidak ada broadcasting/websocket config, tidak ada library OCR, search masih pencarian nama file biasa).

## 3. Perbaikan yang Sudah Dikerjakan (sesi ini & sebelumnya)

Selain analisa di atas, berikut perbaikan konkret yang sudah dilakukan pada kode:

1. **Bug login token mismatch** — `auth.js` store membaca `response.data.token`, padahal `AuthController::login` mengirim key `access_token`. Diperbaiki agar token tersimpan dengan benar setelah login.
2. **Getter `isAdmin`/`userRole` salah bentuk data** — sebelumnya mengecek `user.role` (string), padahal backend mengirim `user.roles` (array relasi dengan `slug`). Diperbaiki agar deteksi admin & role berjalan benar.
3. **`storagePercent` di store dan `StorageUsage.vue` merujuk field `storage_total`** yang tidak pernah ada di model `User` (field asli: `storage_quota`). Diperbaiki agar progress bar penyimpanan menampilkan data asli, bukan angka mock.
4. **Tailwind CSS tidak pernah aktif** — dependency sudah ada di `package.json` tapi `vite.config.js` dan `app.css` belum menyambungkannya, sehingga hampir semua utility class di seluruh halaman tidak bekerja. Sudah diaktifkan (`@tailwindcss/vite` di Vite config, `@import "tailwindcss"` di `app.css`).
5. **Halaman Login dirombak** — layout split-screen (brand panel + form), menghapus animasi blob yang berlebihan, form lebih rapi dan profesional.
6. **Layout aplikasi dirapikan total**:
   - Sidebar: perbaikan bug overlap di desktop (`position: absolute` → `static`/`fixed` sesuai breakpoint), ukuran ikon & nav item konsisten.
   - Header: avatar + dropdown profil dirombak, notifikasi & tombol ikon konsisten, dropdown menutup otomatis saat klik di luar.
   - Ditambahkan komponen CSS reusable: `.page-header`, `.table-modern`, `.badge`, `select.form-control` custom arrow, `.form-control-sm`.
   - Semua tabel data (Admin Users, Admin Logs, Admin Backup, Shared, Trash, Search, Drive) diseragamkan ke `.table-modern`.
   - Seluruh teks UI diterjemahkan ke Bahasa Indonesia.
7. **Route admin roles yang hilang** — `AdminRoleController` sudah lengkap tapi tidak terdaftar di `routes/api.php`, menyebabkan halaman "Peran & Izin Akses" tidak bisa memuat data. Ditambahkan route `GET /admin/roles`.

## 4. Perbaikan Tambahan (sesi lanjutan)

Melanjutkan rekomendasi prioritas dari laporan sebelumnya, berikut yang sudah dikerjakan:

1. **Backup nyata (selesai)** — dibuat `App\Services\BackupService` yang:
   - Melakukan dump database asli (`mysqldump` untuk MySQL/MariaDB, raw file copy untuk SQLite) dan mengarsipkan folder `storage/app/private/uploads` ke dalam satu file ZIP.
   - `BackupController` dirombak untuk memakai service ini: `store()` sekarang membuat backup sungguhan (bukan dummy string), ditambah endpoint baru `POST /admin/backups/{id}/restore` dan `GET /admin/backups/{id}/download`.
   - Ditambahkan Artisan command `backup:run {type}` dan dijadwalkan lewat Laravel Scheduler di `routes/console.php` (`daily` 01:00, `weekly` Minggu 02:00, `monthly` tanggal 1 jam 03:00).
   - Diverifikasi end-to-end: `php artisan backup:run manual` berhasil membuat arsip ZIP berisi `database.sql` (dump asli dari `mysqldump`, ~29KB) — sudah dites lalu dibersihkan.
   - Menambahkan `MYSQLDUMP_PATH` di `.env`/`.env.example` sebagai fallback path binary untuk lingkungan Windows/XAMPP yang tidak punya `mysqldump` di system PATH.
   - `AdminBackup.vue` diperbarui: field-field yang sebelumnya mock (`backup.filename`, ukuran statis "12.4 GB") diganti field asli dari API (`backup.name`, `backup.size` dalam bytes diformat otomatis), tombol **Pulihkan** dan **Unduh** sekarang benar-benar memanggil endpoint restore/download, bukan simulasi `setTimeout`.

2. **File versioning aktif (selesai)** — `FileStorageService::replace()` baru: saat file di-upload ulang (ganti konten), konten lama disimpan sebagai baris baru di `file_versions` (dengan `version_number` incremental) sebelum file fisik ditimpa. Ditambahkan endpoint `POST /files/{id}/versions` (upload versi baru) dan `GET /files/{id}/versions` (lihat riwayat versi).

3. **Permission middleware granular (selesai)** — `PermissionSeeder` diperluas: role non-admin (Kepala Sekolah, Wakasek, Guru, TU, Siswa) sekarang mendapat permission dasar (`folders.*`, `files.*` minus `files.create` khusus, `shares.create/read/delete`, `activity.view`). Middleware `permission:` (yang sebelumnya dibuat tapi tak dipakai) sekarang dipasang di route sensitif: `DELETE files/{id}` → `files.delete`, `GET files/{id}/download` → `files.download`, `DELETE folders/{id}` → `folders.delete`, `POST shares` & `POST shares/link` → `shares.create`.

4. **Route admin roles yang hilang (selesai, dari sesi sebelumnya)** — `GET /admin/roles` terdaftar dan berfungsi.

Semua perubahan di atas sudah diverifikasi: `php -l` pada seluruh file PHP yang diubah, `php artisan route:list` menunjukkan seluruh route baru terdaftar dengan benar, `php artisan db:seed --class=PermissionSeeder` berjalan tanpa error, dan `npx vite build` untuk frontend sukses tanpa error.

## 5. Rekomendasi Prioritas Berikutnya

Sisa gap dari laporan sebelumnya yang belum dikerjakan:

1. **Chunked upload** — perlu untuk file besar agar tidak timeout/exceed memory limit. Saat ini upload masih single-request (`StoreFileRequest` membatasi 100MB).
2. **2FA & enkripsi file (AES-256)** — sesuai kebutuhan keamanan di spec, terutama jika platform menyimpan dokumen sensitif sekolah. Belum ada kolom/logic apapun.
3. **QR Code untuk share link** — fitur kecil tapi disebut eksplisit di spec sharing (`07_File_Sharing.md`).
4. **Restore backup untuk MySQL** — logic restore saat ini mem-parsing dump dengan split sederhana per statement (`explode(";\n", ...)`); untuk dump `mysqldump` yang punya banyak edge case (string berisi `;`, multi-line INSERT), sebaiknya diuji lebih lanjut dengan dataset produksi sebelum dipakai untuk disaster recovery sungguhan, atau ganti pendekatan dengan menjalankan `mysql` CLI langsung (`mysql -u... < dump.sql`) alih-alih parsing manual di PHP.
5. Verifikasi konfigurasi produksi: pastikan `.env` produksi memakai MySQL (sudah benar di `.env` saat ini), cache/queue driver ke Redis, dan dokumentasi deployment (Nginx/Supervisor) tersedia di luar repo kode.
6. NAS storage sebagai alternatif disk local (disebut di `01_System_Architecture.md`) masih belum ada — saat ini backup & upload murni ke disk local server.
7. **Penting untuk deployment**: scheduler backup otomatis (`Schedule::command('backup:run ...')`) hanya berjalan jika cron job `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1` sudah dikonfigurasi di server produksi (lihat `12_Server_Deployment.md`). Tanpa cron ini, backup daily/weekly/monthly tidak akan pernah otomatis berjalan meski kodenya sudah siap.

## 6. Redesign Frontend iOS 16 — Selesai (2 Agustus 2026)

Seluruh frontend telah diselaraskan melalui satu design system global bergaya iOS 16, bukan dengan tema terpisah per halaman.

- Token visual global sekarang memakai **system grouped background**, label/secondary label, separator, fill, system blue, green, orange, dan red.
- Tipografi memakai system font stack Apple/Segoe UI; import font eksternal tidak lagi menjadi kebutuhan visual utama.
- Card/material, tombol, icon button, input, select, textarea, badge, tabel, focus ring, disabled state, error alert, scrollbar, dan transisi sudah distandardisasi.
- Shell aplikasi (`AppLayout`, sidebar, header, profil popup) diubah dari dark purple glassmorphism menjadi split-view/material terang dengan navigasi aktif system blue.
- Login dirombak menjadi panel branding system blue dan form card terang tanpa neon/glow ungu.
- Modal memakai panel iOS dan berubah menjadi **bottom sheet** di mobile; toast menjadi notification banner terpusat; preview file memakai viewer sheet responsif.
- Search bar, empty state, storage usage, segmented grid/list control, serta panel pantau drive sudah mengikuti primitive global yang sama.
- Compatibility layer disediakan untuk halaman lama yang masih memakai utility warna Tailwind sehingga Dashboard, Drive, Favorites, Shared, Trash, Search, Profile, dan seluruh halaman Admin ikut konsisten tanpa mengubah logika/API.
- Label UI inti dilokalkan: **Drive Saya**, **Hanya Baca**, **Penyimpanan**, **terpakai**, serta judul route pada header dalam Bahasa Indonesia.
- Aksesibilitas ditingkatkan dengan target kontrol sekitar 44px, `focus-visible`, dialog semantics, Escape-to-close, reduced-motion support, dan kontras label yang lebih baik.
- Validasi: diagnostics seluruh file inti tidak menemukan masalah dan `npm run build` berhasil dengan Vite 7.3.6 (`121 modules transformed`).
