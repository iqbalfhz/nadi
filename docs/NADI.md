# NADI — Sistem Operasional Kantor Terpadu

Dokumen ini merangkum hasil diskusi perancangan proyek NADI, sebagai referensi untuk tim development.

---

## 1. Ringkasan Proyek

**NADI** adalah platform internal kantor terpusat (web admin + aplikasi mobile) yang menggabungkan berbagai kebutuhan operasional dalam satu ekosistem — satu login, satu backend, banyak modul yang akan terus berkembang seiring waktu.

**Tim:** dikerjakan solo developer.

**Filosofi arsitektur:** satu platform terpadu (modular monolith), bukan sistem-sistem terpisah per kebutuhan. Setiap modul baru cukup ditambahkan sebagai bagian baru dari sistem yang sama, tanpa membangun ekosistem baru dari nol.

---

## 2. Tech Stack & Arsitektur

| Layer                        | Teknologi                                                                                                                                                                                    |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend & Web Admin          | Laravel + **Livewire starter kit** (resmi Laravel 12/13)                                                                                                                                     |
| Admin panel                  | **Filament**, dengan arsitektur **multi-panel**: <br>• `/admin` — untuk admin/HR (kelola semua data, approval, laporan)<br>• `/app` — untuk karyawan biasa (self-service: booking room, dll) |
| Auth & permission            | **Filament Shield** (`bezhansalleh/filament-shield`) + `spatie/laravel-permission`                                                                                                           |
| Real-time (modul Antrian)    | **Laravel Reverb** — berjalan berdampingan dengan Filament tanpa konflik, dipakai khusus untuk update live tanpa refresh                                                                     |
| Mobile app                   | **Flutter** (native/cross-platform, 1 codebase Android+iOS) — mengonsumsi REST API dari backend Laravel yang sama                                                                            |
| Autentikasi API untuk mobile | Laravel Sanctum                                                                                                                                                                              |

**Catatan evaluasi teknologi yang sudah dilakukan:**

- **NativePHP** sempat dipertimbangkan untuk mobile (karena reuse skill PHP/Laravel), tapi **dibatalkan** — sebagian besar plugin native yang dibutuhkan (Geolocation, Scanner/QR, Push Notification) berbayar (Starter Kit $199 atau Ultra $35/bulan). Diputuskan pakai **Flutter** karena semua kebutuhan (kamera, GPS, push notification, scan barcode) tersedia gratis di ekosistemnya (`image_picker`, `geolocator`, `firebase_messaging`, `mobile_scanner`, dll).

### Plugin Filament yang dibutuhkan

| Plugin                                                                                                                      | Kebutuhan                                                                                  |
| --------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `bezhansalleh/filament-shield`                                                                                              | Role & permission (wajib dari awal)                                                        |
| Spatie Activity Log + plugin Filament-nya (mis. `rmsramos/activitylog`)                                                     | Audit trail — siapa membuat/mengubah data apa                                              |
| `saade/filament-fullcalendar` (atau fork aktif `marshmallow/filament-fullcalendar`) / `edissavov/filament-booking-calendar` | Kalender visual untuk modul Booking Room                                                   |
| `pxlrbt/filament-excel`                                                                                                     | Export/import data ke Excel (laporan)                                                      |
| `filament/spatie-laravel-media-library-plugin` (first-party)                                                                | Upload & kelola foto (dipakai di banyak modul: OB, Security, HK, Messenger)                |
| `filament/spatie-laravel-settings-plugin` (first-party)                                                                     | Pengaturan global aplikasi via UI, tanpa ubah kode                                         |
| `barryvdh/laravel-dompdf`                                                                                                   | Generate PDF (bukan plugin Filament, package Laravel biasa — untuk cetak dokumen bernomor) |

---

## 3. Branding

**Nama proyek:** NADI (dari kata "nadi" — pembuluh/urat nadi, merepresentasikan platform sebagai denyut yang menghidupkan operasional kantor).

**Konsep logo:** "N Modular" — huruf N dibentuk dari node/kotak kecil yang saling terhubung dengan garis, merepresentasikan banyak modul kantor yang tersambung jadi satu platform terpadu.

**Palet warna:**
| Warna | Hex | Kegunaan |
|---|---|---|
| Ink Navy | `#16213E` | Warna utama, background ikon, wordmark |
| Coral | `#FF5D3A` | Aksen garis/stroke pada ikon |
| Amber | `#FFB020` | Aksen node/titik pada ikon |
| Paper White | `#FAF8F5` | Latar netral |
| Dark Panel | `#101827` | Latar untuk varian dark mode |

File logo (SVG vector + PNG transparan resolusi tinggi) sudah dibuat, terdiri dari: lockup utama (ikon + wordmark), ikon saja (app icon/favicon), dan varian untuk latar gelap.

---

## 4. Modul-Modul

### 4.1 Penomoran Dokumen

Generate nomor dokumen otomatis dengan format tertentu (logic custom, biasanya via Model Observer/`booted()` event). Terintegrasi dengan kebutuhan cetak PDF dokumen (pakai `laravel-dompdf`).

### 4.2 Booking Room

- Diakses karyawan lewat panel `/app`, tampilan **kalender visual interaktif** (bukan tabel CRUD biasa) menggunakan plugin FullCalendar/Booking Calendar.
- Karyawan klik slot kosong → isi form singkat (ruangan, judul, durasi) → submit.
- Admin di `/admin` bisa melihat semua booking, approve/reject bila diperlukan (tambahkan kolom `status`: pending/approved/rejected jika approval dibutuhkan).

### 4.3 Antrian

- Butuh update **real-time** tanpa refresh manual (misal untuk layar antrian di ruang tunggu) → menggunakan Laravel Reverb + Livewire broadcasting.
- Detail alur/nomor antrian belum dirinci lebih lanjut di diskusi ini — perlu didalami saat mulai development modul ini.

### 4.4 Checklist Area — OB (Office Boy)

| Field      | Keterangan                                |
| ---------- | ----------------------------------------- |
| Area/titik | Dari master data                          |
| Foto       | Multi-upload, dari kamera **atau** galeri |
| Timestamp  | Otomatis                                  |
| Status     | Selesai/belum/terlambat, dll              |

Verifikasi lokasi: **tidak pakai GPS/QR**, cukup pilih dari master data area (tanpa verifikasi lokasi ketat — beda dengan Security).

### 4.5 Checklist Area — Security

| Field            | Keterangan                                                                                  |
| ---------------- | ------------------------------------------------------------------------------------------- |
| Area             | Ditentukan dari hasil **scan QR code** — begitu di-scan, sistem otomatis tahu itu area mana |
| Urutan patroli   | Bebas, tidak harus berurutan                                                                |
| Foto             | Multi-upload                                                                                |
| Laporan kejadian | Field terpisah untuk catat temuan/insiden                                                   |
| Waktu kunjungan  | Tercatat otomatis tiap scan                                                                 |

**Catatan teknis:** perlu master data QR code per titik patroli (`security_checkpoints`), setiap titik dapat 1 kode unik yang di-generate dan ditempel fisik di lokasi. Terpisah dari master data area OB (`ob_areas`) karena kebutuhan datanya beda (satu butuh kode QR, satu tidak).

### 4.6 Messenger / Pengantaran Dokumen

| Field              | Keterangan                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------ |
| Nomor tracking     | Otomatis                                                                                   |
| Pengirim           | Dari user login                                                                            |
| Tujuan             | Nama/departemen penerima                                                                   |
| Deskripsi dokumen  | Bebas/kategori                                                                             |
| Status             | Tersedia (menunggu diambil) → Diambil messenger → Dalam perjalanan → Terkirim              |
| Messenger          | **Self-pickup** — messenger ambil sendiri dari daftar tugas terbuka, bukan di-assign admin |
| Bukti serah terima | **Foto saja** (tanpa tanda tangan digital)                                                 |
| Waktu tiap status  | Log timestamp                                                                              |

**Catatan teknis penting:** karena self-pickup, perlu logic "claim task" dengan database lock/transaction supaya tidak ada race condition saat 2 messenger mengklaim tugas yang sama secara bersamaan.

### 4.7 Checklist HK (Housekeeping Mall) — _Ditunda, dikerjakan paling akhir_

Statusnya **belum final**, tapi sudah ada arah rancangan berikut sebagai referensi awal saat mulai dikerjakan nanti:

- **Cakupan:** semua area di mall — banyak titik per kategori (misal Toilet saja punya ±9 titik: Lt 2/1/UG Zona A&B, Lt GF/LG Zona A&C), belum termasuk Parkiran & Luar Mall. Total kategori diperkirakan **5-10 kategori**.
- **Pola area:** setiap kategori punya pola penamaan berbeda (Toilet: Lantai+Zona; kategori lain berbeda lagi) → nama area disimpan sebagai **teks bebas per kategori**, bukan kolom terstruktur seragam.
- **App ditujukan untuk PENGAWAS** (bukan petugas HK):
  | Field | Sumber |
  |---|---|
  | Pengawas | Otomatis dari user login |
  | Petugas | Ketik bebas (teks manual) oleh pengawas — nama staf HK yang bertugas di area saat dicek |
  | Shift | Dropdown |
  | Keterangan | Teks bebas |
  | Lantai | Nullable, hanya untuk kategori tertentu (mis. Public Area) |
  | Tindak Lanjut | Nullable, hanya untuk kategori tertentu (mis. Public Area) |
  | Foto | Multi-upload |
  | Timestamp | Otomatis |
- **Integrasi notifikasi:** setelah submit, laporan otomatis terkirim ke **grup Telegram** (1 grup untuk semua kategori) — pakai Telegram Bot API (`irazasyed/telegram-bot-sdk` atau HTTP request langsung via Guzzle). Target jangka panjang: grup WhatsApp, tapi Telegram dipilih dulu karena gratis & mudah diimplementasi.
- Contoh format laporan existing (manual, dikirim ke WA) yang jadi referensi field: lihat riwayat diskusi/lampiran gambar format laporan Toilet & Public Area.

---

## 5. Keputusan Arsitektural Penting (untuk dipahami tim dev)

1. **Semua karyawan kantor akan punya akun login** ke sistem NADI.
2. **Satu backend, satu database** untuk semua modul — bukan sistem terpisah-pisah. Modul baru = folder/menu baru dalam sistem yang sama.
3. **Filament multi-panel** dipakai untuk memisahkan pengalaman admin (`/admin`) vs karyawan (`/app`) tanpa perlu membangun frontend terpisah.
4. **Mobile (Flutter) dikerjakan belakangan** — prioritas saat ini adalah menyelesaikan web & admin panel dulu sampai stabil. Pastikan API backend dirancang lengkap dari awal (pakai Sanctum) supaya nanti tinggal dikonsumsi Flutter tanpa restrukturisasi backend.
5. Modul checklist (OB, Security, HK) menggunakan pola **hardcoded per modul** (bukan generic form builder) — karena variasi field antar kategori ternyata tidak terlalu ekstrem untuk butuh sistem builder yang kompleks. Cukup gunakan kolom nullable untuk field yang sifatnya opsional per kategori.

---

## 6. Belum Dibahas / Perlu Didalami Lebih Lanjut

- Struktur database detail (ERD/tabel & relasi) — belum dirancang secara formal, baru sebatas field-level per modul di atas.
- Detail alur modul Antrian (jenis antrian, nomor urut, tampilan layar publik, dll).
- Detail final modul Checklist HK (master data kategori & area, dan detail relasi Telegram bot per kategori jika diperlukan nanti).
- Struktur folder/modul di kode (disarankan modular monolith: `app/Modules/<NamaModul>/` per modul).
