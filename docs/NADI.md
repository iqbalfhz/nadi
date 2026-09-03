# NADI — Sistem Operasional Kantor Terpadu

Dokumen ini merangkum hasil diskusi perancangan proyek NADI, sebagai referensi untuk tim development.

Bagian 1-5 adalah rancangan awal, ditulis sebelum pengerjaan dimulai. Bagian 6-11 ditambahkan pada 1 September 2026 dan mencatat **apa yang benar-benar dibangun** — termasuk di mana hasilnya berbeda dari rancangan, modul yang muncul belakangan di luar dokumen ini, cara sistem ini dijalankan di production, dan jebakan yang sudah terlanjur ditemui.

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
| Admin panel                  | **Filament 5** (bukan v4 — starter kit ini pakai Livewire 4, dan Filament baru support Livewire 4 mulai v5), dengan arsitektur **multi-panel**: <br>• `/admin` — untuk admin/HR (kelola semua data, approval, laporan)<br>• `/app` — untuk karyawan biasa (self-service: booking room, dll)<br>Kedua panel share satu login (halaman login Fortify/Flux yang sudah ada, bukan halaman login bawaan Filament). |
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
| `guava/calendar`                                                                                                            | Kalender visual untuk modul Booking Room — 3 opsi awal (`saade/filament-fullcalendar`, fork `marshmallow/...`, `edissavov/filament-booking-calendar`) semuanya mentok di Filament 3, tidak ada yang support Filament 5 |
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

### 4.7 Checklist HK (Housekeeping Mall)

Rancangan awal berikut sempat ditandai belum final. Keputusan akhirnya ada di blok setelah daftar ini.

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

**Keputusan final (1 September 2026).** Rancangan di atas ditutup lewat diskusi, dan beberapa hal berubah:

| Pertanyaan | Keputusan |
|---|---|
| Cakupan satu submit | **Satu titik = satu laporan**, bukan satu ronde berisi banyak titik. Satu tabel, pola sama seperti Checklist OB |
| Penilaian kondisi | **Ditambahkan** — Bersih / Perlu Perbaikan / Kotor. Tanpa ini laporan tidak bisa direkap jadi angka |
| Shift | Pagi / Siang / Malam |
| Telegram | Dikerjakan sekaligus, dan **semua laporan** dikirim ke grup, bukan hanya yang bermasalah |
| Field "Lantai" | Dinyalakan per kategori lewat master data (`hk_categories.requires_floor`), bukan di-hardcode — daftar kategori belum final, jadi kategori baru tidak boleh butuh deploy |
| Field "Tindak Lanjut" | Muncul **dan wajib** saat kondisi bukan "Bersih". Pemicunya temuan, bukan kategori: Public Area yang bersih pun tidak butuh tindak lanjut |

**Kenapa HK tidak menyalin Checklist OB.** OB adalah bukti kerja — petugas melapor pekerjaannya sendiri. HK adalah **laporan inspeksi**: pengawas melapor tentang pekerjaan orang lain. Karena itu satu baris data memuat dua orang (`user_id` pengawas dari user login, `staff_name` petugas sebagai teks bebas — staf HK bukan pemegang akun NADI).

**Master data** dibuat sebagai dua resource CRUD terpisah (Kategori dan Titik), **bukan** Repeater bersarang seperti Bazar. Dengan sekitar 90 titik, daftar datar yang bisa dicari lebih mudah dikelola, sekaligus menghindari jebakan simpan/hapus Repeater bersarang.

---

### Status implementasi (per 1 September 2026)

**Seluruh modul 4.1–4.7 sudah dibangun dan berjalan di production.** Tabel ini mencatat di mana hasil akhirnya berbeda dari rancangan awal — perbedaan itu yang paling sering membingungkan saat dokumen ini dibaca ulang.

| Modul | Catatan perbedaan dari rancangan |
|---|---|
| 4.1 Penomoran Dokumen | Ditambah backup harian ke Google Drive (lihat bagian 9) |
| 4.2 Booking Room | Kalender memakai `guava/calendar`; tiga plugin yang disebut di bagian 2 semuanya mentok di Filament 3 |
| 4.3 Antrian | Lengkap: kiosk, layar tunggu, panel operator, rotasi iklan. Printer kiosk **belum diuji ke perangkat asli** |
| 4.4 Checklist OB | Sesuai rancangan |
| 4.5 Checklist Security | Scan QR memakai aplikasi kamera bawaan HP yang membuka URL — tidak ada pemindai di dalam aplikasi |
| 4.6 Messenger | `claim()` memakai row lock, dibuktikan lewat test dua klaim bersamaan |
| 4.7 Checklist HK | Rancangan difinalkan 1 Sep 2026, lihat blok di atas |

Selain itu ada **empat modul yang tidak ada di dokumen ini sama sekali** — lihat bagian 7.

---

## 5. Keputusan Arsitektural Penting (untuk dipahami tim dev)

1. **Semua karyawan kantor akan punya akun login** ke sistem NADI.
2. **Satu backend, satu database** untuk semua modul — bukan sistem terpisah-pisah. Modul baru = folder/menu baru dalam sistem yang sama.
3. **Filament multi-panel** dipakai untuk memisahkan pengalaman admin (`/admin`) vs karyawan (`/app`) tanpa perlu membangun frontend terpisah.
4. **Mobile (Flutter) dikerjakan belakangan** — prioritas saat ini adalah menyelesaikan web & admin panel dulu sampai stabil. Pastikan API backend dirancang lengkap dari awal (pakai Sanctum) supaya nanti tinggal dikonsumsi Flutter tanpa restrukturisasi backend.
5. Modul checklist (OB, Security, HK) menggunakan pola **hardcoded per modul** (bukan generic form builder) — karena variasi field antar kategori ternyata tidak terlalu ekstrem untuk butuh sistem builder yang kompleks. Cukup gunakan kolom nullable untuk field yang sifatnya opsional per kategori.
6. **Rahasia yang perlu diganti admin disimpan di database, bukan `.env`** — token bot Telegram, kredensial Google Drive, PIN kiosk. Memakai `spatie/laravel-settings` + halaman Pengaturan Filament, dengan atribut `#[ShouldBeEncrypted]` untuk yang sensitif. Alasannya: rotasi kredensial tidak boleh menuntut deploy ulang.
7. **Foto bukti kerja tidak pernah di disk publik.** Disk `public` menerbitkan berkas di URL yang bisa ditebak tanpa login. Semua foto (OB, Security, Messenger, HK) memakai disk `internal` yang privat, dibuka lewat URL bertanda tangan yang kedaluwarsa.
8. **Laporan yang sudah dikirim tidak bisa diedit dari antarmuka** (OB, Security, HK, dan penjualan). Halaman edit memang tidak dibuat. Catatan yang bisa diubah pelakunya sendiri tidak layak dipercaya.
9. **Role menggambarkan pekerjaan, bukan departemen** — lihat bagian 8.1.
10. **Pekerjaan yang memanggil layanan luar berjalan lewat antrean.** Pengiriman Telegram tidak pernah boleh menggagalkan penyimpanan laporan; pengawas di lapangan tidak menunggu jaringan.
11. **Antarmuka sepenuhnya berbahasa Indonesia**, termasuk pesan validasi, halaman login, dan pesan error. Pesan teknis mentah tidak pernah ditampilkan ke layar — masuk ke log lewat `report()`, layar mendapat penjelasan yang bisa ditindaklanjuti.

---

## 6. Yang sudah terjawab, dan yang masih terbuka

Bagian ini semula berjudul "Belum Dibahas". Sebagian besar sudah terjawab lewat implementasi.

**Sudah terjawab:**

- **Struktur database** — 40 migration. ERD formal tidak pernah dibuat; setiap tabel punya model, factory, dan test, dan itu yang jadi acuan.
- **Detail alur Antrian** — selesai seluruhnya (kiosk ambil nomor, layar tunggu real-time, panel operator, rotasi iklan di layar).
- **Detail Checklist HK** — lihat 4.7.
- **Struktur folder** — saran `app/Modules/<NamaModul>/` **tidak jadi dipakai**. Kode mengikuti struktur bawaan Filament (`app/Filament/Resources/<Nama>/`, `app/Models/`, `app/Filament/App/` untuk panel karyawan). Pemisahan antar modul sudah cukup terwakili oleh navigation group di kedua panel; menambah lapisan folder sendiri hanya akan melawan konvensi Filament tanpa manfaat nyata.

**Masih terbuka:**

- **Mobile Flutter + API Sanctum** — **API-nya sudah selesai** (3 Sep 2026). Sanctum mode token di `/api/v1`, login + 2FA tanpa sesi, gerbang akses mobile, kunci idempotensi untuk outbox offline, unggah foto dua langkah, dan keempat modul lapangan: Checklist OB, Patroli Security, Inspeksi HK, Tugas Messenger. Kontraknya di [API-MOBILE.md](API-MOBILE.md). **Yang belum: aplikasi Flutter-nya sendiri.**
- **2FA** — tersedia lewat Fortify dan halamannya sudah ada, tapi belum diaktifkan di akun mana pun.
- **Printer** — kode cetak untuk kiosk Antrian dan struk POS sudah ada (`window.print()` + CSS 80mm), tapi **belum pernah diuji ke perangkat fisik**.
- **Cakupan backup** — sengaja hanya modul Penomoran Dokumen (lihat 9.4).
- **Pindah backup ke S3** — sudah disepakati sebagai langkah berikutnya (lihat 9.4).

---

## 7. Modul di luar dokumen ini

Empat modul dibangun atas permintaan yang muncul setelah dokumen ini ditulis. Semuanya mengikuti pola arsitektural yang sama seperti modul 4.x.

### 7.1 POS Tiket Event

Penjualan tiket untuk acara berbayar tahunan di mall. Harga reguler dan harga member ditentukan per event.

- Kasir adalah **karyawan mana pun** yang sudah login dan punya permission — tidak ada role kasir khusus di lapangan.
- Tidak ada database member; status member hanya toggle manual, dan hasil scan barcode disimpan sebagai teks referensi tanpa divalidasi.
- **Harga di-snapshot ke baris transaksi.** Admin mengubah harga event setelah ada penjualan tidak boleh mengubah riwayat pendapatan.
- Laporan penjualan di `/app` **sengaja tidak dibatasi per kasir** — tutup kas setelah acara berarti menjumlahkan semua shift, bukan hanya milik sendiri. Ini kebalikan dari semua resource `/app` lainnya.
- Struk dicetak dengan `window.print()` + CSS 80mm.

### 7.2 Bazar Kios

Bazar multi-vendor: beberapa kios eksternal berjualan, sebagian per 100 gram (durian), sebagian per pcs.

- Kios diketik manual per acara, bukan master data yang dipakai ulang.
- Harga ditentukan per kombinasi kios + produk, dengan satuan sendiri-sendiri.
- `VendorSale::sellFor()` mengunci **dua baris**: produk (sumber harga) dan bazar (gerbang buka/tutup). `bazaar_id` dan `vendor_id` selalu diturunkan dari produk yang dikunci, tidak pernah dari input pemanggil.
- **PB1 (pajak daerah)** dihitung per kios dengan tarif masing-masing dan disimpan di kolom terpisah — uang pajak dikumpulkan atas nama kios tapi bukan milik kios, jadi tidak boleh tercampur saat bagi hasil.
- Laporan settlement per kios tersedia sebagai widget tabel.

### 7.3 Short Link

Memendekkan URL panjang (Google Drive/Docs) agar rapi saat dibagikan.

- Route publik `/s/{code}` tanpa autentikasi, mencatat jumlah klik.
- **Tujuan link dibatasi allowlist host** (`config/short-links.php`). Tujuan di luar daftar menampilkan halaman konfirmasi lebih dulu, bukan langsung mengalihkan — ini menutup penyalahgunaan domain kantor sebagai pengalih ke situs sembarangan.

### 7.4 Generator Barcode & QR

Membuat QR code dan barcode 1D (Code128, EAN-13, Code39) untuk keperluan kantor.

- Gambar **tidak disimpan** ke storage — di-generate ulang setiap kali diunduh, mengikuti pola QR titik patroli Security.
- Riwayat siapa membuat apa tetap dicatat.

---

## 8. Sistem lintas modul

Hal-hal yang tidak dimiliki satu modul, tapi menopang semuanya.

### 8.1 Role & permission

Role menggambarkan **pekerjaan**, bukan departemen. Dibangun tiga lapis yang saling menumpuk, di `database/seeders/NadiRoleSeeder.php`:

| Lapis | Isi | Contoh |
|---|---|---|
| 1. Dasar | `karyawan` — yang boleh dipakai semua orang | booking ruangan, kirim dokumen, short link |
| 2. Jabatan | satu role per pekerjaan | `ob`, `security`, `kurir`, `operator-antrian`, `kasir-event`, `kasir-bazar`, `pengawas-hk` |
| 3. Admin modul | pengelolaan per modul | `admin-dokumen`, `admin-antrian`, `admin-hk`, dan seterusnya |

Seorang OB mendapat `karyawan` + `ob`. Kepala bagian bisa mendapat `karyawan` + `ob` + `admin-ob`. Seeder memakai `givePermissionTo`, **tidak pernah** `syncPermissions`, supaya permission yang ditambahkan manual lewat UI tidak terhapus saat seeder dijalankan ulang.

Konstanta `User::APP_PANEL_PERMISSIONS` menentukan permission mana yang dianggap "hanya `/app`". Memiliki permission di luar daftar itu berarti pemiliknya boleh masuk `/admin`. **Setiap modul baru wajib menambahkan permission `/app`-nya ke konstanta ini** — ini langkah yang paling sering terlupa.

### 8.2 Riwayat Aktivitas

Audit trail di `/admin`, dibangun di atas `spatie/laravel-activitylog`. Mencatat lima kategori:

| Kategori | Isi |
|---|---|
| `data` | pembuatan, perubahan, dan penghapusan data di 24 model |
| `akses` | login, logout, login gagal |
| `izin` | pemberian dan pencabutan role/permission |
| `akses-data` | pembukaan foto bukti kerja |
| `ditolak` | percobaan membuka halaman yang tidak diizinkan (403) |

Setiap entri menyimpan alamat IP. Atribut sensitif (password, token, PIN, secret) disaring secara global sebelum ditulis. Retensi 365 hari, dibersihkan otomatis oleh scheduler.

### 8.3 Dashboard

`/admin` punya dashboard laporan dengan filter periode (7 hari, 30 hari, bulan ini, dan seterusnya) plus rentang tanggal kustom, menampilkan statistik operasional dan penjualan beserta grafiknya. Setiap widget disaring berdasarkan permission pemakainya. `/app` punya dashboard sederhana berisi tautan cepat.

### 8.4 Halaman error

Semua kode status punya halaman sendiri berbahasa Indonesia (401, 402, 403, 404, 419, 429, 500, 503, plus penampung 4xx dan 5xx). Halaman ini sengaja mandiri: CSS inline, tanpa `@vite`, tanpa query database — halaman yang tampil ketika sesuatu sudah rusak tidak boleh bergantung pada hal yang mungkin ikut rusak.

Tombol tujuannya menyesuaikan: karyawan yang tidak berhak masuk `/admin` diarahkan ke `/app`, bukan ke halaman yang baru saja menolaknya.

### 8.5 Bahasa & halaman publik

`APP_LOCALE=id`, didukung `lang/id/` (validasi, autentikasi, paginasi) dan `lang/id.json` (halaman login dan pengaturan bawaan starter kit). Setiap resource Filament mendeklarasikan `$modelLabel` dan `$pluralModelLabel` secara eksplisit — tanpa itu Filament menjamakkan nama kelas dengan aturan Inggris dan menghasilkan "Dokumens", "Pengirimen", "Patroli Securities".

Dua halaman publik tanpa login: `/kebijakan-privasi` dan `/syarat-ketentuan`. Keduanya ada karena Google mewajibkannya untuk mempublikasikan aplikasi OAuth (lihat 9.4), dan memang pantas dimiliki aplikasi yang menyimpan foto dan data karyawan.

---

## 9. Deployment & operasional

### 9.1 Bentuk deployment

Berjalan di VPS sendiri lewat **Coolify**, dari `docker-compose.yml` di repositori ini. Deploy dipicu otomatis oleh `git push origin main` (GitHub Actions).

Lima container dari satu image:

| Container | Tugas |
|---|---|
| `app` | web (FrankenPHP) |
| `reverb` | WebSocket untuk modul Antrian |
| `scheduler` | `schedule:work` — backup harian, pembersihan log |
| `queue` | `queue:work` — pengiriman Telegram |
| `nadi-redis` | session dan cache |

Hanya `app` yang menyetel `RUN_SETUP=true`; hanya container itu yang menjalankan migration, seeder, dan pembuatan cache saat start. Tanpa pembagian ini, keempat container akan berebut menjalankan migration bersamaan.

### 9.2 Antrean

`QUEUE_CONNECTION=database`. Halaman **Pengaturan → Telegram** menampilkan status antrean (bersih / menunggu / tertahan / gagal) beserta tombol untuk memproses manual dan mengirim ulang yang gagal.

Ini ada karena pengalaman nyata: pekerja antrean pernah mati tanpa satu pun tanda di dalam aplikasi — laporan tersimpan, layar hijau, tapi tidak ada yang terkirim.

### 9.3 Notifikasi Telegram

Laporan Checklist HK dikirim ke satu grup Telegram lewat Bot API, memakai HTTP langsung tanpa paket tambahan. Foto diunggah sebagai multipart karena tersimpan di disk privat dan tidak bisa ditarik Telegram lewat URL. Satu foto memakai `sendPhoto`, banyak foto memakai `sendMediaGroup` supaya satu ronde tidak membanjiri grup dengan pesan beruntun.

Bot token dan chat ID diisi di **Pengaturan → Telegram**, dengan tombol **Kirim Tes** untuk memverifikasi tanpa perlu membuat laporan palsu.

### 9.4 Backup

Mencadangkan **hanya modul Penomoran Dokumen** ke Google Drive, setiap hari pukul 01:00. Cakupan sempit ini keputusan sadar, bukan kelalaian.

Sempat tidak pernah berjalan sama sekali di production karena **tiga kegagalan bertumpuk**, masing-masing baru terlihat setelah yang sebelumnya diperbaiki:

1. **Refresh token kedaluwarsa.** Layar persetujuan OAuth berstatus *Testing* hanya memberi token berumur 7 hari. Diperbaiki dengan mempublikasikan aplikasi ke *In production* — yang lebih dulu menuntut halaman kebijakan privasi dan syarat ketentuan.
2. **`mysqldump` tidak ada di image.** `spatie/laravel-backup` memanggil program itu; ekstensi PHP hanya membuat aplikasi bisa *terhubung* ke MySQL. Ditambahkan `mariadb-client` ke Dockerfile.
3. **Verifikasi TLS.** Client MariaDB memverifikasi sertifikat server, sedangkan MySQL Coolify memakai sertifikat self-signed. Butuh `skip_ssl` **dan** `set_ssl_flag` bernilai `skip-ssl` sekaligus di blok `dump` milik koneksi mysql.

**Langkah berikutnya yang sudah disepakati: pindah ke penyimpanan S3.** Kunci statis tidak pernah kedaluwarsa dan tidak butuh layar persetujuan — jauh lebih cocok untuk backup malam hari tanpa pengawasan daripada OAuth yang dirancang untuk aplikasi berpenghadap manusia.

---

## 10. Jebakan yang sudah ditemui

Dicatat karena semuanya gagal secara diam-diam — tidak ada error, hanya perilaku yang salah.

| Jebakan | Gejalanya | Pelajarannya |
|---|---|---|
| **Nama service Docker terlalu umum** | Ter-logout mendadak dan halaman lambat selama berminggu-minggu, dikira wajar. `getent hosts redis` ternyata mengembalikan **dua** alamat — project lain di VPS ini juga punya service bernama `redis`, dan koneksi dibagi acak ke keduanya | Di VPS berisi banyak project, jangan pernah memberi nama generik (`redis`, `db`, `cache`). Kalau ada yang gagal *kadang-kadang* dan bukan selalu, periksa resolusi nama lebih dulu |
| **Tidak ada queue worker** | Job mengendap di tabel `jobs` selamanya tanpa error | Menambahkan job berarti memastikan ada yang menjalankannya |
| **`shield:generate --all` menimpa policy** | Policy yang sudah disesuaikan kembali ke bentuk generik — audit log jadi bisa dihapus, cek "pemilik record" hilang | Selalu pakai `--ignore-existing-policies`, dan periksa `git diff app/Policies` setelahnya |
| **`discoverWidgets()` Filament** | Widget yang tidak didaftarkan di `->widgets([...])` tetap muncul di Dashboard | Pakai `protected static bool $isDiscovered = false` untuk widget yang hanya dipakai di halaman tertentu |
| **Kolom form Filament** | Separuh halaman kosong | Setiap komponen (termasuk Section dan Repeater) default-nya hanya selebar satu kolom; butuh `columnSpanFull()` |
| **Grup navigasi tidak didaftarkan** | Menu muncul di posisi acak tanpa ikon | Grup harus terdaftar di `navigationGroups()` panelnya |
| **`latest()` tanpa nama kolom** | Filter "bazar terbaru" kadang memilih yang salah | `latest()` hanya mengurutkan `created_at`; baris yang dibuat pada detik yang sama diurutkan sesuka database. Pakai `latest('id')` |
| **Blok `@php` di view Filament** | Halaman 500 dengan pesan `Attempt to read property "childNodes" on null` | Pindahkan perhitungan ke method `#[Computed]` di kelas halamannya |
| **`APP_LOCALE` di env mengalahkan config** | Antarmuka tetap berbahasa Inggris walau default di kode sudah diubah | Ubah juga di Coolify, bukan hanya di repositori |

---

## 11. Menjalankan & memverifikasi

Gerbang wajib sebelum commit (lihat juga `CLAUDE.md`):

```
composer lint          # perbaiki gaya kode
composer types:check   # analisis statis
php artisan test       # seluruh test
```

Jalankan terpisah — `composer test` menabrak batas waktu 300 detik milik composer.

Ada 382 test di 58 berkas. Sebagian di antaranya sengaja menjaga hal yang tidak terlihat mata: bahasa antarmuka, lebar kolom form, kelengkapan grup navigasi, flag TLS untuk dump database, dan keterjangkauan halaman kebijakan tanpa login.
