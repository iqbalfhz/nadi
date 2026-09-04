# Pertanyaan untuk Sisi Backend (Laravel) — dengan Jawaban

Daftar hal yang **tidak bisa dipastikan dari sisi mobile** selama membangun aplikasi Flutter NADI.

Pertanyaannya dijawab pada **4 September 2026**. Teks pertanyaan aslinya dibiarkan utuh; jawaban ada di blok **→ Jawaban** di bawah masing-masing.

Tiga di antaranya bukan sekadar dijawab — **backend-nya diubah**, karena temuannya benar.

---

## Ringkasan: yang berubah di aplikasi

| Butir | Tindakan di Flutter |
|---|---|
| **B1** | **Buat tab Riwayat HK.** Endpoint-nya ternyata sudah ada, cuma tidak terdokumentasi |
| **B2** | **Hapus cabang penanganan objek tunggal** di `MessengerRepository.proofUrls`. Selalu array |
| **B3** | **Hapus tambalan "segarkan daftar lalu koreksi sendiri".** Pesannya sekarang membedakan. Dan **simpan kunci klaim ke penyimpanan permanen**, bukan memori |
| **B5** | **Tampilkan satu tindakan, bukan dua.** 404 dan 410 sekarang berbeda |
| A1, B4 | Tidak ada perubahan. Dokumennya yang diperbaiki |
| C1, C2, C3 | Tidak ada perubahan, tapi baca batas barunya di C2 |

Semua jawaban di bawah juga sudah masuk permanen ke [API-MOBILE.md](API-MOBILE.md), dan **dikunci sebagai test** di `tests/Feature/Api/ApiContractTest.php` — kalau salah satu janji ini dilanggar nanti, suite yang memberi tahu, bukan Anda yang menemukannya di lapangan.

---

## A. Ketidaksesuaian yang sudah terbukti

### A1. `Idempotency-Key` tidak diwajibkan di `auth/login`

**Pertanyaan:** apakah middleware idempotensi memang sengaja tidak dipasang di rute `auth/*`, atau tabel di §3 yang terlalu menyamaratakan?

> ### → Jawaban: **Anda benar. Dokumen saya yang salah.**
>
> `auth/login` dan `auth/two-factor-challenge` memang berada di luar grup middleware idempotensi, dan itu disengaja: tidak ada catatan yang bisa terduplikasi, dan ponsel yang belum berhasil masuk belum punya apa pun untuk dipakai ulang.
>
> Kalimat "setiap POST ke `/api/v1`" di §3 terlalu menyamaratakan. Sudah diperbaiki, dan pengecualiannya sekarang punya test sendiri (`test_login_does_not_require_an_idempotency_key`) supaya tidak berubah diam-diam.
>
> **Tidak ada yang perlu diubah di aplikasi.** Mengirim header di sana tetap tidak masalah — hanya diabaikan.

---

## B. Endpoint yang dibutuhkan tapi tidak ada di dokumen

### B1. Daftar inspeksi HK — `GET /api/v1/hk/inspections`

**Akibat di mobile:** modul HK tidak punya tab Riwayat.

> ### → Jawaban: **Endpoint-nya sudah ada sejak awal. Saya yang lupa menulisnya.**
>
> `GET /hk/inspections` dan `GET /hk/inspections/{id}` sudah ada sejak modul HK dibuat. Bentuknya persis seperti `GET /ob/checklists`: milik pengirim sendiri, terbaru dulu, 20 per halaman, dengan `meta.current_page` dan `meta.last_page`.
>
> Keputusan Anda untuk tidak mengarangnya sudah benar — tapi dalam hal ini memang tidak perlu dikarang.
>
> **Silakan buat tab Riwayat HK-nya sekarang.**

### B2. Konfirmasi path foto untuk tiga modul lain

**Pertanyaan:** apakah ketiganya benar-benar mengembalikan bentuk yang sama persis? Khususnya `/messenger/tasks/{id}/proof` — buktinya `singleFile`, jadi mungkin mengembalikan objek tunggal.

> ### → Jawaban: **Keempatnya selalu array. Termasuk `/proof`.**
>
> `singleFile` membatasi *isinya* jadi paling banyak satu foto, tapi bentuk responsnya tetap array — `[{...}]` atau `[]` kalau belum ada foto. Tidak pernah objek tunggal, tidak pernah `null`.
>
> Field-nya sama persis di keempatnya: `data[].id`, `data[].url`, `data[].expires_at`, URL berumur 30 menit.
>
> **Hapus cabang yang menangani objek tunggal.** Kecurigaan Anda masuk akal — nama `/proof` yang berbeda memang mengundangnya — tapi menyimpan dua cabang berarti menyimpan tebakan, dan Anda benar soal itu.
>
> Dikunci oleh `test_every_photo_endpoint_returns_the_same_shape`, yang memanggil keempatnya dalam satu test.

### B3. Apakah `claim` mengenali `Idempotency-Key`?

**Pertanyaan:** mana yang berlaku? Dan kalau yang kedua, bisakah 409 membedakan "diambil orang lain" dari "sudah Anda ambil sendiri"?

> ### → Jawaban: **Idempotensi aktif. Tapi temuan Anda tetap valid, dan backend-nya sudah saya ubah.**
>
> **Bagian pertama:** ya, `claim` ada di dalam grup idempotensi. Kunci yang sama → balasan sukses yang pertama diputar ulang, lengkap dengan header `Idempotency-Replayed: true`. Kurirnya melihat "berhasil diambil", yang memang benar.
>
> **Bagian kedua — ini yang penting.** Skenario Anda tetap bisa terjadi, karena kuncinya Anda simpan **di memori**: aplikasi ditutup sebelum balasan sampai → kunci hilang → klaim ulang dengan kunci baru → server melihatnya sebagai klaim baru.
>
> Dulu pesannya *"Tugas ini sudah diambil messenger lain."* — salah dan menyesatkan, persis seperti yang Anda tulis. **Sekarang dibedakan:**
>
> ```json
> { "message": "Tugas ini sudah diambil messenger lain." }
> { "message": "Tugas ini sudah Anda ambil. Ada di daftar Tugas Saya." }
> ```
>
> **Dua hal untuk aplikasi:**
> 1. **Hapus tambalannya.** Pesannya sekarang sudah mengarahkan sendiri; tidak perlu menyegarkan daftar untuk membiarkan kurir mengoreksi sendiri.
> 2. **Simpan kunci klaim ke penyimpanan permanen**, bukan memori. Dengan begitu cabang kedua hampir tidak pernah terpakai — yang terjadi cuma pemutaran ulang balasan sukses.

### B4. Bentuk `code` pada `GET /security/checkpoints/{code}`

**Pertanyaan:** apakah `code` dijamin aman dipakai di path — alfanumerik saja?

> ### → Jawaban: **Ya, dijamin. Terverifikasi, bukan asumsi.**
>
> `code` dihasilkan `Str::random(32)` milik Laravel, yang mem-base64 byte acak lalu **membuang `/`, `+`, dan `=`**. Hasilnya selalu 32 karakter alfanumerik murni. Saya jalankan untuk memastikan, bukan menyimpulkan dari dokumentasi.
>
> Aman ditaruh langsung di path tanpa encoding. **Tidak ada yang perlu diubah.**

### B5. Apakah 404 pos bisa membedakan dua sebabnya?

**Pertanyaan:** apakah `message` pada kedua kasus itu sudah berbeda?

> ### → Jawaban: **Dulu tidak. Sekarang bisa — status dan pesannya keduanya berbeda.**
>
> Anda benar: dulu kedua sebab menghasilkan 404 dengan pesan generik yang sama. Satpam yang berdiri di pos yang baru dicabut akan memindai ulang berkali-kali tanpa hasil.
>
> | Status | Artinya | Tindakan satpam |
> |---|---|---|
> | **404** | Kode tidak dikenali | Stikernya salah atau bukan milik NADI. Pindai ulang |
> | **410** | Pos sudah dinonaktifkan admin | Pindai ulang tidak akan pernah berhasil. Laporkan ke pengawas |
>
> `message`-nya masing-masing sudah menyebutkan tindakan yang benar, jadi tetap bisa ditampilkan apa adanya.
>
> **Tampilkan satu tindakan saja sekarang**, tidak perlu menyarankan keduanya sekaligus.

---

## C. Pertanyaan tentang batas dan skala

### C1. Apakah daftar master data pernah berhalaman?

> ### → Jawaban: **Tidak pernah, dan sekarang itu janji yang dijaga.**
>
> `GET /ob/areas` dan `GET /hk/categories` selalu mengembalikan seluruh isinya sebagai array polos tanpa `meta`, berapa pun banyaknya baris.
>
> Kekhawatiran Anda tepat sasaran: kalau salah satunya dipaginasi nanti, cache di HP akan diam-diam cuma berisi halaman pertama, dan **tidak ada apa pun di sisi HP yang bisa menyadarinya**. Karena itu saya tidak cukup menjawab "tidak" — saya kunci dengan test (`test_master_data_is_never_paginated`, 25 baris tiap daftar) supaya perubahan seperti itu tidak bisa lolos diam-diam.
>
> **Silakan terus meng-cache seluruhnya.**

### C2. Apakah POST laporan punya batas percobaan?

> ### → Jawaban: **Dulu tidak ada sama sekali. Sekarang ada, dan sengaja longgar.**
>
> Ini yang paling mengejutkan saat saya periksa: grup middleware `api` cuma berisi `SubstituteBindings`. Laravel 11 menghapus `throttle:api` dari default, dan saya tidak pernah mengaktifkannya — jadi seluruh endpoint laporan dan unggahan **tanpa batas apa pun**. Login terlindungi karena punya limiter sendiri di controller.
>
> | Cakupan | Batas |
> |---|---|
> | `POST /auth/login` | 5 per menit per email + IP |
> | Semua endpoint lain (per token) | **240 per menit** |
>
> Angka 240 dipilih dari kasus yang Anda sebut: dua belas laporan beserta fotonya ≈ 50 request dalam beberapa detik. Batas konvensional 60/menit justru akan mencekik persis kasus yang seluruh rancangan offline ini dibuat untuk melayani. Ada test yang mensimulasikan flush satu shift penuh (24 request berturut-turut) dan memastikan tidak kena.
>
> **429 menyertakan `Retry-After`** (detik) — pakai itu alih-alih menebak jeda sendiri.
>
> Kalau batas ini pernah kena saat pengiriman normal, berarti angkanya salah. Laporkan, jangan disiasati di aplikasi.

### C3. Kuota unggah foto

> ### → Jawaban: **Tidak ada kuota.**
>
> Tidak ada batas jumlah unggahan per pengguna per hari, dan tidak ada batas total penyimpanan. Yang ada hanya:
>
> - **10 MB per berkas**, JPG/PNG/WEBP
> - **Foto yatim dihapus setelah 7 hari** (terunggah tapi laporannya tak pernah dikirim)
>
> Kompresi di HP yang Anda lakukan sudah cukup. Puluhan foto per shift per petugas tidak masalah.

---

## D. Yang sudah terverifikasi cocok dengan dokumen

Terima kasih sudah mencatatnya — ini menghemat waktu saya. Semuanya masih berlaku setelah perubahan di atas; 541 test backend hijau.

---

## E. Catatan tambahan

**Perangkat emulator di panel admin.** Terima kasih, sudah dicatat — aman dikeluarkan lewat tombol "Keluarkan dari semua perangkat" di /admin → Pengguna.

**`submitted_at`.** Perilaku penjepitannya **sudah diuji di sisi backend**, jadi Anda tidak perlu mengirim jam HP yang salah untuk membuktikannya:

- Waktu di masa depan → ditarik ke sekarang
- Lebih dari 7 hari lalu → ditarik ke 7 hari lalu
- Di antara keduanya → disimpan apa adanya

Laporannya **tidak pernah ditolak** karena jam HP salah. Itu disengaja: kehilangan laporan gara-gara jam yang meleset akan meniadakan seluruh alasan membolehkannya dikirim offline.

---

## Kalau ada pertanyaan berikutnya

Tulis dengan format yang sama — apa yang tertulis di dokumen, apa yang benar-benar terjadi, dan apa akibatnya bagi aplikasi. Tiga dari sebelas butir di atas menghasilkan perubahan backend justru karena ditulis seperti itu.
