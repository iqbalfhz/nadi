# AI Coding Agent Guidelines

Standar kerja & aturan wajib bagi AI Agent di repository ini.

## 1. Project Context & Environment
- **Environment:** Linux / VPS (Ubuntu 24.04, FrankenPHP, PHP 8.x, MySQL).
- **Project Type:** Web Application (PHP Native / Laravel).
- Gunakan environment variables (`.env`) untuk data sensitif. Jangan pernah simpan kredensial/API keys langsung di dalam kode.

## 2. Code Quality & Security
- Tulis kode yang modular, mudah dibaca, dan aman dari kerentanan umum (SQL Injection & XSS).
- Sebelum menyelesaikan tugas, pastikan kode telah divalidasi dan bebas dari kesalahan sintaks (`php -l` atau unit test jika ada).
- Validasi wajib sebelum commit: `composer test` (menjalankan `pint --test`, `phpstan analyse`, lalu `php artisan test`). Jalankan `composer lint` untuk auto-fix style sebelum itu. Jangan commit/push jika salah satu langkah ini gagal.

## 3. Git Workflow & Mandatory CI/CD Trigger (WAJIB)
1. **Granular Commit:** Lakukan `git commit` untuk setiap 1 tugas/fitur kecil yang selesai dikerjakan. Gunakan format konvensi pesan commit (contoh: `feat: ...` atau `fix: ...`).
2. **Auto Push:** Setelah komit berhasil dan dipastikan bebas error, WAJIB menjalankan:
   `git push origin main`

   > Catatan: `git push` ini adalah pemicu (trigger) otomatis untuk pipeline CI/CD (GitHub Actions) agar perubahan ter-deploy langsung ke VPS. Ini pre-otorisasi standing agent boleh push ke `origin main` tanpa konfirmasi ulang tiap kali, selama commit tersebut sudah lolos validasi (poin 2) dan bebas error.

## 4. Restrictions (Yang Dilarang)
- Dilarang melakukan `git push` jika kodingan masih bermasalah/error.
- Dilarang menjalankan perintah terminal berskala destruktif (`rm -rf /`, `DROP DATABASE`, dll) tanpa persetujuan.
- Dilarang mengubah struktur folder utama aplikasi tanpa instruksi spesifik.

## 5. Project: NADI
Proyek ini adalah **NADI** — sistem operasional kantor terpadu (web admin + mobile), solo developer. Desain lengkap (tech stack, modul, keputusan arsitektural) ada di [docs/NADI.MD](docs/NADI.MD) — baca dokumen itu untuk konteks sebelum mengerjakan modul baru.

Ringkasan cepat:
- **Stack:** Laravel 13 + Livewire starter kit, **Filament** multi-panel (`/admin` untuk admin/HR, `/app` untuk karyawan), Filament Shield + `spatie/laravel-permission`, Laravel Reverb (real-time, modul Antrian), Sanctum (API untuk mobile Flutter — dikerjakan belakangan).
- **Arsitektur:** modular monolith, satu backend/satu database. Modul baru = folder/menu baru dalam sistem yang sama, disarankan di `app/Modules/<NamaModul>/`.
- **Laravel Boost** sudah terpasang ([boost.json](boost.json)) dengan skills: `laravel-best-practices`, `livewire-development`, `fluxui-development`, `fortify-development`, `tailwindcss-development`, `infer-conventions`. Ikuti guidelines dari skill-skill ini alih-alih pola Laravel generik.
