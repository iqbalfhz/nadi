# API Mobile NADI — Panduan untuk Programer Flutter

Dokumen ini ditujukan untuk orang yang akan membangun aplikasi Flutter-nya dan belum pernah melihat kode backend NADI. Tidak perlu membaca kode PHP mana pun untuk mengikuti dokumen ini.

Contoh request dan respons di bawah **disalin dari server yang benar-benar berjalan**, bukan dikarang.

Terakhir diperbarui: 6 September 2026 · Perubahan antar-tanggal dicatat di [API-PERUBAHAN.md](API-PERUBAHAN.md)

> **Dokumen ini yang berlaku, dan ia tinggal di repo backend** ([`nadi/docs/API-MOBILE.md`](https://github.com/iqbalfhz/nadi/blob/main/docs/API-MOBILE.md)). Kalau Anda membaca salinannya di repo lain, salinan itu bisa saja tertinggal — cocokkan tanggal di atas sebelum memercayainya.

Base URL produksi diberikan terpisah · Semua endpoint diawali `/api/v1`

---

## 1. NADI itu apa

NADI adalah sistem operasional internal sebuah mall. Sisi webnya sudah berjalan di produksi sejak Agustus 2026 dan dipakai staf kantor lewat dua panel: `/admin` untuk admin dan HR, `/app` untuk karyawan.

Aplikasi Flutter ini bukan versi mobile dari seluruh sistem itu. Ia hanya membawa **empat modul lapangan** — pekerjaan yang dikerjakan sambil berjalan keliling mall, di mana HP jelas lebih berguna daripada laptop:

| Modul | Kunci | Siapa memakainya |
|---|---|---|
| **Checklist OB** | `ob` | Petugas kebersihan melapor setelah selesai membersihkan satu titik: pilih area, foto, catatan singkat |
| **Patroli Security** | `security` | Satpam memindai QR di pos, memotret keadaan, dan menulis laporan kejadian bila ada |
| **Inspeksi HK** | `hk` | Pengawas housekeeping menilai satu titik: kategori, titik, kondisi, foto, dan tindak lanjut bila kondisinya bermasalah |
| **Tugas Messenger** | `messenger` | Kurir mengambil permintaan antar dokumen, menandai berangkat, lalu mengunggah foto bukti saat terkirim |

**Keempat modul sudah bisa dipanggil.** Semuanya mengikuti pola yang sama: ambil master data → unggah foto satu per satu → kirim laporannya dengan `photo_ids`.

### Tiga hal yang membentuk seluruh rancangan API ini

Sebelum masuk ke endpoint, tiga kenyataan lapangan ini menjelaskan kenapa API-nya berbentuk seperti ini. Kalau ketiganya dipahami, sisanya masuk akal sendiri.

1. **Sinyal sering hilang.** Basement, area parkir, tangga darurat, toilet — di situlah pekerjaannya. Laporan harus bisa dibuat tanpa sinyal dan dikirim belakangan.
2. **Foto itu besar dan koneksinya buruk.** Satu laporan bisa membawa tiga foto 10 MB lewat wifi mall yang putus-nyambung.
3. **Foto adalah barang bukti.** Tidak ada satu pun foto yang boleh punya URL publik permanen.

---

## 2. Autentikasi

### 2.1 Login biasa

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "budi@tangcity.com",
  "password": "rahasia",
  "device_name": "Redmi Note 12"
}
```

`device_name` wajib. Nilainya muncul di panel admin sebagai nama perangkat, jadi pakai merek/model HP-nya — bukan string tetap seperti `"flutter"`. Admin memakainya untuk memutuskan perangkat mana yang dikeluarkan saat ada HP hilang.

**200 OK**

```json
{
  "token": "1|H1hqmJ8sYzt38CukEadF1W9uIKpZBgskBBXiP4oP4d326197",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@tangcity.com",
    "locale": null,
    "department": null,
    "modules": ["ob"]
  }
}
```

`user` bentuknya sama persis dengan balasan `GET /me`, termasuk blok `app` yang opsional itu (lihat §6) — jadi pengecekan versi sudah bisa dilakukan sejak layar login, tanpa menunggu panggilan berikutnya.

Simpan `token` di penyimpanan aman (`flutter_secure_storage`, bukan `SharedPreferences`). Kirim di setiap request berikutnya:

```
Authorization: Bearer 1|H1hqmJ8sYzt38CukEadF1W9uIKpZBgskBBXiP4oP4d326197
```

**Token tidak kedaluwarsa sendiri.** Ia berhenti berlaku kalau admin menonaktifkan akunnya, admin menekan "Keluarkan dari semua perangkat", atau pengguna logout. Artinya: petugas tidak akan ter-logout di tengah shift, tapi aplikasi tetap harus menangani 401 kapan saja (lihat [bagian 7](#7-katalog-error)).

### 2.2 `modules` menentukan isi layar utama

`user.modules` adalah daftar modul yang boleh dibuka akun ini. **Bangun menu utama dari daftar ini**, jangan dari daftar tetap di kode Flutter.

Alasannya praktis: satu orang bisa merangkap (`["ob", "messenger"]`), dan admin bisa mengubah hak akses kapan saja lewat panel. Aplikasi yang menampilkan menu tetap akan memberi tombol yang berujung 403.

Panggil `GET /api/v1/me` setiap kali aplikasi dibuka untuk menyegarkan daftar ini — peran yang dicabut pagi ini harus hilang pagi ini juga, bukan setelah login ulang.

### 2.3 Login dengan 2FA

Sebagian akun menyalakan autentikasi dua langkah. Untuk akun itu, `POST /auth/login` **tidak mengembalikan token**:

```json
{
  "two_factor": true,
  "challenge_token": "2|abc123..."
}
```

Cek keberadaan field `two_factor` untuk membedakan kedua alur.

`challenge_token` adalah token berumur **5 menit** yang tidak bisa dipakai ke endpoint lain — mencobanya ke `/me` akan dijawab 403. Ia hanya untuk langkah kedua:

```http
POST /api/v1/auth/two-factor-challenge
Authorization: Bearer 2|abc123...
Content-Type: application/json

{
  "code": "123456",
  "device_name": "Redmi Note 12"
}
```

Bisa juga memakai kode pemulihan sebagai ganti `code`:

```json
{ "recovery_code": "abcd-efgh", "device_name": "Redmi Note 12" }
```

Balasannya sama persis dengan login biasa (`token` + `user`). Kode pemulihan **hangus setelah dipakai sekali**, dan `challenge_token` juga langsung mati begitu ditukar — jangan simpan.

Bila kodenya salah: **422** dengan error di field `code` atau `recovery_code`. Batasnya 5 percobaan per menit.

### 2.4 Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer <token>
```

Mencabut hanya token yang sedang dipakai. Perangkat lain milik orang yang sama tetap masuk.

### 2.5 Batas percobaan login

5 kali per menit per kombinasi email + alamat IP. Melebihi itu: **429**, dan percobaan berikutnya ditolak walau passwordnya benar. Pesannya menyebutkan sisa detiknya.

---

## 3. Aturan pertama: kunci idempotensi

**Ini bagian terpenting di seluruh dokumen.** Bacalah sebelum menulis kode pengiriman apa pun.

### Masalahnya

Petugas menekan "Kirim" di basement. Aplikasi mengirim laporan. Koneksi putus **sebelum balasan sampai**.

Laporannya masuk atau tidak? Aplikasi tidak bisa tahu. Dua-duanya buruk:

- Kirim ulang → berisiko laporan dobel di panel admin
- Tidak kirim ulang → berisiko laporan hilang, padahal petugas sudah merasa selesai

### Aturannya

**Setiap POST ke `/api/v1` wajib membawa header `Idempotency-Key` berisi UUID v4 — kecuali `auth/login`, `auth/two-factor-challenge`, dan `crash`.**

Kedua endpoint autentikasi itu di luar aturan ini: tidak ada catatan yang bisa terduplikasi, dan ponsel yang belum berhasil masuk belum punya apa pun untuk dipakai ulang. Mengirim headernya di sana tidak masalah, hanya diabaikan.

`crash` di luar aturan ini karena alasan yang berlawanan: di situ pengulangan justru datanya, bukan kesalahan yang perlu ditelan. Server menggabungkan yang identik dengan caranya sendiri — lihat §6.

```
Idempotency-Key: 3f2504e0-4f89-11d3-9a0c-0305e82c3301
```

Aturan pemakaiannya cuma satu, tapi harus benar:

> **Satu laporan = satu UUID, dibuat saat laporan dibuat di HP, disimpan bersama laporan itu di database lokal, dan dipakai ulang di setiap percobaan kirim.**

Jangan pernah membuat UUID baru saat mengirim ulang. UUID baru = laporan baru di mata server.

### Yang dilakukan server

| Keadaan | Balasan |
|---|---|
| Kunci baru | Diproses normal, hasilnya disimpan |
| Kunci sama, request sama, sudah pernah sukses | **Balasan yang persis sama** dengan yang pertama, plus header `Idempotency-Replayed: true` |
| Kunci sama, tapi request pertama masih diproses | 409 — tunggu sebentar lalu ulangi |
| Kunci sama dipakai ke endpoint berbeda | 409 — ini bug aplikasi |
| Tidak ada header sama sekali | 400 |

Karena percobaan kedua mengembalikan balasan yang sama, aplikasi bisa memperlakukan hasil kirim ulang persis seperti hasil kirim pertama. Tidak perlu logika khusus.

**Kunci diingat 7 hari.** Cukup untuk HP yang tertinggal di loker sepanjang akhir pekan.

**Laporan yang ditolak (422) boleh dikirim ulang dengan kunci yang sama** setelah diperbaiki. Server hanya mengingat yang berhasil.

### Bentuk outbox yang disarankan

```
Tabel lokal: laporan_tertunda
  id                INTEGER PRIMARY KEY
  idempotency_key   TEXT     -- UUID v4, dibuat sekali di sini
  modul             TEXT     -- 'ob' | 'security' | 'hk' | 'messenger'
  payload           TEXT     -- JSON
  photo_ids         TEXT     -- id foto yang sudah berhasil diunggah
  photo_paths       TEXT     -- berkas lokal yang belum terunggah
  submitted_at      TEXT     -- ISO 8601, waktu petugas menekan Kirim
  status            TEXT     -- 'menunggu' | 'mengirim' | 'gagal'
  percobaan         INTEGER
```

Alurnya: simpan dulu ke tabel ini dan beri tahu petugas laporannya tersimpan, **baru** coba kirim. Jangan pernah membuat petugas menunggu jaringan.

---

## 4. Upload foto: dua langkah

Foto **tidak** dikirim bersama laporannya. Ada dua langkah terpisah.

### Kenapa

Kalau digabung, setiap percobaan ulang mengirim ulang semua fotonya. Tiga foto 10 MB yang putus di 90% berarti mengulang 30 MB dari nol — dan di basement itu kondisi normal, bukan kasus langka.

Dipisah, setiap foto yang berhasil **tetap tersimpan sebagai kemajuan**. Yang gagal paling banyak satu foto, dan laporannya sendiri cuma beberapa ratus byte JSON.

### Langkah 1 — unggah satu foto

```http
POST /api/v1/uploads
Authorization: Bearer <token>
Idempotency-Key: <uuid untuk foto ini>
Content-Type: multipart/form-data

photo: <berkas>
```

**201 Created**

```json
{
  "data": {
    "id": "01a06695-0767-73c4-9dd8-bae49e2976b2",
    "expires_at": "2026-09-10T16:23:57+07:00"
  }
}
```

Batasnya: **JPG, PNG, atau WEBP, maksimal 10 MB**. Kompres di sisi HP sebelum mengunggah — foto kamera modern sering melebihi batas ini, dan menolak di HP jauh lebih murah daripada mengunggah 12 MB lalu ditolak.

Satu request satu foto. Untuk tiga foto, tiga request — masing-masing dengan `Idempotency-Key` sendiri.

Foto yang sudah diunggah tapi laporannya tak pernah dikirim akan **dihapus otomatis setelah 7 hari** (`expires_at`).

### Langkah 2 — kirim laporannya

Sertakan id-id foto yang berhasil:

```json
{ "photo_ids": ["01a06695-...", "0e2f1c33-..."] }
```

Id foto milik orang lain akan ditolak sebagai "tidak ditemukan".

### Kalau foto berhasil tapi laporan gagal

Ini justru kasus yang dirancang: simpan `photo_ids` yang sudah berhasil ke outbox lokal, dan pada percobaan berikutnya **jangan unggah ulang** — kirim laporannya saja dengan id yang sudah ada.

---

## 5. Bentuk data

### Sukses

Satu objek:

```json
{ "data": { ... } }
```

Daftar berhalaman:

```json
{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1, "from": 1, "to": 1 }
}
```

Pakai `meta.current_page` dan `meta.last_page` untuk paginasi; abaikan `meta.links` (itu untuk paginator HTML).

**Master data tidak pernah berhalaman.** `GET /ob/areas` dan `GET /hk/categories` selalu mengembalikan seluruh isinya sebagai array polos tanpa `meta`, berapa pun banyaknya. Ini janji yang disengaja, bukan kebetulan: aplikasi meng-cache keduanya untuk dipakai offline, dan memaginasi salah satunya nanti akan diam-diam membuat cache di HP cuma berisi halaman pertama — tanpa ada apa pun di sisi HP yang bisa menyadarinya. Dijaga oleh `ApiContractTest::test_master_data_is_never_paginated`.

### Waktu

Semua waktu yang **dikeluarkan** server berbentuk ISO 8601 dengan offset, zona **Asia/Jakarta**: `2026-09-03T14:23:57+07:00`.

Untuk waktu yang **dikirim** aplikasi (`submitted_at`, `occurred_at`): **selalu sertakan penanda zona** — `Z` atau `+07:00`, keduanya sama benarnya. Server mengubahnya ke Asia/Jakarta sebelum menyimpan, jadi `2026-09-05T16:58:00Z` dan `2026-09-05T23:58:00+07:00` menghasilkan catatan yang identik.

Waktu tanpa offset sama sekali (`2026-09-05T23:58:00`) masih diterima dan dibaca sebagai waktu Jakarta, tapi jangan mengandalkan itu — kirim offsetnya.

---

## 6. Referensi endpoint

Semua endpoint kecuali `auth/login` butuh header `Authorization: Bearer <token>`.
Semua POST butuh header `Idempotency-Key` — **kecuali `POST /crash`**, yang justru tidak boleh dianggap duplikat (lihat bagiannya di bawah).

### `GET /api/v1/me`

Profil dan daftar modul. Panggil setiap aplikasi dibuka.

```json
{
  "data": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@tangcity.com",
    "locale": null,
    "department": null,
    "modules": ["ob"],
    "app": {
      "latest_version": "1.0.3+4",
      "minimum_version": "1.0.0+1",
      "download_url": "https://nadi.example.com/apk"
    }
  }
}
```

`locale` bernilai `null`, `"id"`, atau `"en"` — bahasa pilihan pengguna di web. `null` berarti mengikuti bahasa aplikasi (Indonesia). Pesan error dari server mengikuti pilihan ini.

#### Blok `app` — versi yang seharusnya dipakai

APK dibagikan langsung ke petugas, bukan lewat Play Store. Tidak ada pembaruan otomatis, dan tidak ada yang memberi tahu petugas bahwa versinya sudah usang. Blok ini yang menutup celah itu.

| Field | Yang harus dilakukan aplikasi |
|---|---|
| `latest_version` | Kalau versi terpasang lebih rendah, tampilkan spanduk **"Versi baru tersedia"**. Petugas tetap bisa bekerja seperti biasa |
| `minimum_version` | Kalau versi terpasang lebih rendah, **blokir** dan minta perbarui |
| `download_url` | Tujuan tombol di spanduk itu. Bisa `null` — kalau kosong, cukup beri tahu ada versi baru tanpa tautan |

Formatnya sama persis dengan `version:` di `pubspec.yaml` — `MAJOR.MINOR.PATCH+BUILD`. **Yang dibandingkan adalah angka `+BUILD`**, karena itu satu-satunya bagian yang dijamin selalu naik.

**Seluruh blok `app` bisa tidak ada.** Itu keadaan normal, bukan error: selama admin belum mengisi versinya di `/admin`, servernya memang tidak mengirim apa pun. Aplikasi harus berjalan seperti biasa — tanpa spanduk, tanpa blokir. `minimum_version` dan `download_url` masing-masing juga bisa `null`.

`minimum_version` sengaja jarang dinaikkan. Petugas yang terblokir di tengah shift tidak bisa melapor sama sekali, dan laporan yang sudah mengantre di outbox-nya ikut tertahan sampai dia sempat memperbarui.

### `GET /api/v1/ob/areas`

Daftar area yang bisa dipilih. Kecil dan jarang berubah — **cache di HP**, karena tanpa daftar ini petugas di basement tidak punya apa pun untuk dipilih. Segarkan saat aplikasi dibuka dan saat ada sinyal.

```json
{
  "data": [
    { "id": 2, "name": "Lobi Utama" },
    { "id": 1, "name": "Toilet Lantai 2" }
  ]
}
```

Area yang sudah dipensiunkan admin tidak muncul di sini, dan **ditolak** kalau tetap dikirim — jadi cache yang basi bisa menyebabkan 422. Tangani dengan menyegarkan daftar lalu meminta petugas memilih ulang.

### `POST /api/v1/ob/checklists`

```http
POST /api/v1/ob/checklists
Authorization: Bearer <token>
Idempotency-Key: 3f2504e0-4f89-11d3-9a0c-0305e82c3301
Content-Type: application/json

{
  "ob_area_id": 1,
  "notes": "Lantai basah, sudah dipel ulang.",
  "photo_ids": ["01a06695-0767-73c4-9dd8-bae49e2976b2"],
  "submitted_at": "2026-09-03T14:23:57+07:00"
}
```

| Field | Wajib | Aturan |
|---|---|---|
| `ob_area_id` | ya | Harus area yang masih aktif |
| `photo_ids` | ya | 1–10 id, semuanya milik pengirim |
| `notes` | tidak | Maksimal 1000 karakter |
| `submitted_at` | tidak | Lihat di bawah |

**`submitted_at` penting untuk mode offline.** Isi dengan waktu petugas menekan "Kirim" di HP, bukan waktu aplikasi berhasil mengirim ke server. Tanpa ini, dua belas laporan yang mengantre sejak pagi akan tercatat semuanya di jam yang sama saat sinyal kembali — dan pengawas yang membacanya tidak belajar apa pun.

Server menyimpan keduanya: `submitted_at` (klaim petugas) dan `created_at` (saat server menerima). Nilai yang mustahil — di masa depan, atau lebih dari 7 hari lalu — **ditarik ke rentang wajar, bukan ditolak**. Jam HP yang salah tidak boleh membuat laporan hilang.

**201 Created**

```json
{
  "data": {
    "id": 1,
    "area": { "id": 1, "name": "Toilet Lantai 2" },
    "notes": "Lantai basah, sudah dipel ulang.",
    "photo_count": 1,
    "submitted_at": "2026-09-03T14:23:57+07:00",
    "created_at": "2026-09-03T16:23:57+07:00"
  }
}
```

### `GET /api/v1/ob/checklists`

Riwayat laporan **milik pengguna sendiri**, terbaru dulu, 20 per halaman. Tambahkan `?page=2` untuk halaman berikutnya. Laporan orang lain tidak akan pernah muncul di sini.

### `GET /api/v1/ob/checklists/{id}`

Satu laporan. Milik orang lain → **403**.

### `GET /api/v1/ob/checklists/{id}/photos`

```json
{
  "data": [
    {
      "id": "804ca371-8c7e-4245-922e-69040b68dd90",
      "url": "https://.../1/6R19vXge7cYs....jpg?expiration=1788429238",
      "expires_at": "2026-09-03T16:53:58+07:00"
    }
  ]
}
```

Tiga hal yang harus dipahami tentang `url` ini:

1. **Berumur 30 menit.** Setelah itu mati. Jangan disimpan di database lokal — panggil ulang endpoint ini saat dibutuhkan.
2. **Pakai apa adanya.** Jangan diutak-atik, jangan dibongkar, jangan ditambahi header `Authorization` (URL-nya sudah bertanda tangan sendiri). Foto ada di penyimpanan privat dan tidak punya alamat permanen — itu disengaja.
3. **Memanggil endpoint ini tercatat di jejak audit.** Setiap pembukaan foto masuk ke Riwayat Aktivitas. Jangan memanggilnya untuk pra-muat spekulatif — panggil saat pengguna benar-benar membuka fotonya.

Ketiga modul lain punya endpoint foto yang bentuknya identik: `/security/patrols/{id}/photos`, `/hk/inspections/{id}/photos`, dan `/messenger/tasks/{id}/proof`.

**Keempatnya selalu mengembalikan array**, termasuk `/proof` — meski koleksi buktinya `singleFile` dan isinya paling banyak satu elemen. Tidak pernah objek tunggal, tidak pernah `null`; kalau belum ada foto, `data` adalah array kosong. Dijaga oleh `ApiContractTest::test_every_photo_endpoint_returns_the_same_shape`.

---

### Patroli Security

**Tidak ada endpoint yang membagikan daftar pos.** Ini disengaja: kode di stiker QR itulah bukti bahwa satpam benar-benar sampai di pos. Kalau aplikasi bisa mengunduh semua kodenya, satu ronde penuh bisa dilaporkan dari kantin. Aplikasi hanya boleh bertanya tentang kode yang sudah dipegang — dan kode itu hanya didapat dengan mendatanginya.

#### Apa yang sebenarnya ada di stiker

**QR-nya memuat URL, bukan kode telanjang:**

```
https://<domain>/app/security-scan/<kode 32 karakter alfanumerik>
```

Itu disengaja: satpam yang aplikasinya belum terpasang bisa mengarahkan kamera bawaan ke stiker yang sama dan mendarat di formulir web. Selama masa peralihan, jalur itu yang dipakai.

**Aplikasi tidak perlu mem-parsing URL ini.** Kirim hasil pindai apa adanya ke endpoint di bawah; server yang mengekstrak kodenya. Format stiker itu milik backend, dan parsing di HP berarti perubahan pola URL akan mematikan setiap handset yang belum di-update.

#### `GET /api/v1/security/scan?scanned=<hasil pindai>`

**Ini yang harus dipanggil aplikasi.** Isi `scanned` dengan apa pun yang dibaca kamera, URL-encoded. Diterima: URL penuh, URL dengan query, URL dengan slash penutup, dan kode telanjang.

```
GET /api/v1/security/scan?scanned=https%3A%2F%2Fnadi.example.com%2Fapp%2Fsecurity-scan%2FaB3xK9...
```

Balasannya sama persis dengan endpoint di bawah. `scanned` kosong → **400**.

#### `GET /api/v1/security/checkpoints/{code}`

Bentuk lama, menerima **kode telanjang saja**. Masih berfungsi, tapi tidak bisa menerima URL — slash di dalamnya membuat request mendarat di rute lain, atau tidak terkirim sama sekali.

Menerjemahkan satu kode hasil pindai jadi nama pos.

`code` **dijamin alfanumerik** (32 karakter, dari `Str::random()` yang membuang `/`, `+`, dan `=`), jadi aman ditaruh langsung di path tanpa encoding.

Dua kegagalan, dan **sengaja dibedakan** karena tindakan satpamnya berbeda:

| Status | Artinya | Yang harus dilakukan satpam |
|---|---|---|
| **404** | Kode tidak dikenali | Stikernya salah atau bukan milik NADI. Pindai ulang |
| **410** | Pos sudah dinonaktifkan admin | Pindai ulang tidak akan pernah berhasil. Laporkan ke pengawas |

Tampilkan `message`-nya apa adanya; keduanya sudah menyebutkan tindakan yang benar.

```json
{ "data": { "id": 1, "name": "Pos Parkir P2" } }
```

Perhatikan: `code` **tidak** dikembalikan. Jangan menyusun daftar kode di HP.

Konsekuensinya untuk mode offline: pemindaian pertama di sebuah pos butuh sinyal untuk menampilkan namanya. Simpan pasangan kode→nama yang sudah pernah berhasil, dan untuk kode yang belum dikenal saat offline cukup tampilkan "Pos akan dikonfirmasi saat laporan terkirim" — laporannya sendiri tetap bisa diantre.

#### `POST /api/v1/security/patrols`

```json
{
  "checkpoint_code": "aB3xK9...",
  "incident_report": "Pintu darurat lantai 3 tidak terkunci.",
  "photo_ids": ["01a06695-..."],
  "submitted_at": "2026-09-03T16:22:33+07:00"
}
```

| Field | Wajib | Aturan |
|---|---|---|
| `checkpoint_code` | ya | **Hasil pindai mentah atau kode telanjang** — keduanya diterima. Pos harus masih aktif |
| `photo_ids` | ya | 1–10 |
| `incident_report` | tidak | Maks 1000. Kosongkan kalau tidak ada temuan |
| `submitted_at` | tidak | Waktu satpam sampai di pos |

**201:**

```json
{
  "data": {
    "id": 1,
    "checkpoint": { "id": 1, "name": "Pos Parkir P2" },
    "incident_report": "Pintu darurat lantai 3 tidak terkunci.",
    "photo_count": 1,
    "submitted_at": "2026-09-03T16:22:33+07:00",
    "created_at": "2026-09-03T17:02:33+07:00"
  }
}
```

#### `GET /api/v1/security/patrols`

Ronde milik satpam itu sendiri, berhalaman. Ronde rekan tidak muncul — pengawasan ada di `/admin`.

---

### Inspeksi HK

Modul paling rumit: picker dua tingkat dan dua field bersyarat.

#### `GET /api/v1/hk/categories`

Kategori **beserta titik-titiknya** dalam satu panggilan. Cache seluruhnya — `requires_floor` ikut di sini, dan itulah yang membuat aplikasi bisa merender field Lantai bersyarat tanpa bertanya ke server.

```json
{
  "data": [
    {
      "id": 1,
      "name": "Toilet",
      "requires_floor": true,
      "areas": [ { "id": 1, "name": "Lt 2 Zona A" } ]
    }
  ]
}
```

#### `GET /api/v1/hk/options`

Pilihan shift dan kondisi. **Jangan hardcode di Flutter** — labelnya bisa berubah, dan `needs_follow_up` adalah sumber kebenaran untuk kapan Tindak Lanjut wajib.

```json
{
  "data": {
    "shifts": [
      { "value": "pagi", "label": "Pagi" },
      { "value": "siang", "label": "Siang" },
      { "value": "malam", "label": "Malam" }
    ],
    "conditions": [
      { "value": "bersih", "label": "Bersih", "needs_follow_up": false },
      { "value": "perlu_perbaikan", "label": "Perlu Perbaikan", "needs_follow_up": true },
      { "value": "kotor", "label": "Kotor", "needs_follow_up": true }
    ]
  }
}
```

#### `POST /api/v1/hk/inspections`

| Field | Wajib | Aturan |
|---|---|---|
| `hk_area_id` | ya | Titik yang masih aktif |
| `staff_name` | ya | Maks 255. Nama petugas yang **diperiksa**, bukan pengawas |
| `shift` | ya | Dari `/hk/options` |
| `condition` | ya | Dari `/hk/options` |
| `floor` | **bersyarat** | Wajib bila kategori titik itu `requires_floor: true` |
| `follow_up` | **bersyarat** | Wajib bila kondisinya `needs_follow_up: true`. Maks 1000 |
| `notes` | tidak | Maks 1000 |
| `photo_ids` | ya | 1–10 |
| `submitted_at` | tidak | |

**Tidak ada `hk_category_id`.** Server menurunkannya dari titik yang dipilih; nilai yang dikirim akan diabaikan. Laporan di `/admin` difilter per kategori, jadi pasangan yang tidak cocok akan mendaratkan laporan di tempat yang tidak dilihat siapa pun.

Field yang tidak sesuai syarat **dibuang**, bukan ditolak: `floor` untuk kategori yang tidak memintanya, dan `follow_up` untuk kondisi "Bersih", disimpan sebagai `null`.

**201:**

```json
{
  "data": {
    "id": 1,
    "category": { "id": 1, "name": "Toilet", "requires_floor": true },
    "area": { "id": 1, "name": "Lt 2 Zona A" },
    "staff_name": "Siti",
    "shift": "pagi", "shift_label": "Pagi",
    "condition": "perlu_perbaikan", "condition_label": "Perlu Perbaikan",
    "floor": "Lantai 2",
    "notes": "Wastafel nomor 2 bocor.",
    "follow_up": "Sudah dilaporkan ke teknisi.",
    "photo_count": 1,
    "submitted_at": null,
    "created_at": "2026-09-03T17:02:33+07:00"
  }
}
```

Setiap laporan HK juga dikirim server ke grup Telegram. Aplikasi tidak perlu melakukan apa pun untuk itu, dan Telegram yang mati tidak akan menggagalkan laporan.

#### `GET /api/v1/hk/inspections`

Riwayat inspeksi milik pengawas itu sendiri, terbaru dulu, 20 per halaman — bentuknya sama persis dengan `GET /ob/checklists`. Ada juga `GET /hk/inspections/{id}` untuk satu laporan.

(Kedua endpoint ini sudah ada sejak awal; sebelumnya luput dari dokumen ini.)

---

### Tugas Messenger

Satu-satunya modul dengan alur status:

```
available → picked_up → in_transit → delivered
```

#### `GET /api/v1/messenger/tasks/open`

Tugas yang belum diambil siapa pun — **tidak dibatasi per kurir**, karena itulah inti self-pickup.

```json
{
  "data": [
    {
      "id": 1,
      "tracking_number": "MSG-KMMWUGII",
      "origin": "Front Office Lt 1",
      "destination": "Kantor Manajemen Lt 5",
      "requester": { "name": "Sinta", "department": "HRD" },
      "document_description": "Berkas kontrak vendor",
      "status": "available",
      "status_label": "Tersedia",
      "claimed_at": null,
      "in_transit_at": null,
      "delivered_at": null,
      "created_at": "2026-09-03T17:02:33+07:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

`origin` adalah **tempat kurir mengambil** dokumennya, `destination` tempat mengantarkannya. Keduanya wajib ditampilkan di kartu tugas — tanpa `origin`, kurir tahu tujuan tapi tidak tahu harus ke mana lebih dulu.

`requester` adalah pemohonnya, untuk ditanya kalau dokumennya tidak ada di tempat. `requester.department` bisa `null`. `origin` bisa `null` hanya untuk permintaan lama; permintaan baru mewajibkannya.

Keduanya ada di semua balasan Messenger: `open`, `mine`, `claim`, `transit`, dan `deliver`.

#### `GET /api/v1/messenger/tasks/mine`

Yang sedang dibawa kurir ini (`picked_up` dan `in_transit`). Yang sudah terkirim hilang dari daftar.

#### `POST /api/v1/messenger/tasks/{id}/claim`

> ### ⚠ Satu-satunya langkah yang TIDAK boleh diantre offline
>
> Dua kurir bisa menekan "Ambil" pada tugas yang sama. Server menyelesaikannya dengan penguncian baris dan memberi tahu yang kalah. Klaim yang diantre offline akan **terlihat berhasil di HP** lalu kalah diam-diam — kurir berjalan mengambil dokumen yang sudah diambil orang lain.
>
> Wajibkan online untuk langkah ini. Langkah setelahnya boleh diantre.

Tidak ada body. Berhasil → **200** dengan status `picked_up`.

Gagal → **409**, dengan **dua pesan berbeda** yang harus dibedakan aplikasi karena tindakannya berbeda:

```json
{ "message": "Tugas ini sudah diambil messenger lain." }
{ "message": "Tugas ini sudah Anda ambil. Ada di daftar Tugas Saya." }
```

Yang kedua terjadi kalau balasan pertama hilang dan aplikasi mengulang **dengan kunci baru** (misalnya aplikasi sempat ditutup sebelum kuncinya sempat disimpan). Tugasnya ada di tangan kurir itu sendiri — arahkan dia ke Tugas Saya, jangan suruh mencari tugas lain.

Kalau kuncinya **sama**, ini tidak akan pernah terjadi: server memutar ulang balasan sukses yang pertama. Menyimpan kunci klaim ke penyimpanan permanen, bukan hanya memori, membuat cabang kedua itu hampir tak pernah terpakai.

Kedua pesan aman ditampilkan apa adanya.

#### `POST /api/v1/messenger/tasks/{id}/transit`

Tidak ada body. Hanya berlaku untuk tugas ber-status `picked_up` milik kurir itu; selain itu **409**.

#### `POST /api/v1/messenger/tasks/{id}/deliver`

```json
{ "photo_id": "01a06695-..." }
```

**Satu foto, bukan array** — koleksi buktinya `singleFile`. Hanya berlaku untuk `in_transit` milik kurir itu.

Tanpa foto: **422**. Pengiriman tanpa bukti itu klaim, bukan catatan.

### Laporan kegagalan aplikasi

#### `POST /api/v1/crash`

Satu-satunya jalur agar kegagalan di lapangan sampai ke pengembang. APK dibagikan langsung, bukan lewat Play Store, jadi tidak ada laporan crash bawaan sama sekali — tanpa endpoint ini, aplikasi yang gagal di HP petugas jam 3 pagi hanya diketahui HP itu sendiri.

```json
{
  "message": "Null check operator used on a null value",
  "stack": "#0 _PatrolFormScreenState._pindai ...",
  "app_version": "1.0.3+4",
  "platform": "android",
  "device": "Xiaomi 24115RA8EG",
  "os_version": "16",
  "occurred_at": "2026-09-05T14:23:57+07:00"
}
```

Balasannya **201 tanpa isi**. Tidak ada yang perlu dibaca.

**Hanya `message` yang wajib.** Sisanya boleh dikosongkan — sebuah laporan kegagalan datang saat sesuatu sudah tidak beres, dan menolaknya karena nama perangkat kosong berarti membuang satu-satunya bukti yang ada. String kosong diperlakukan sama dengan tidak dikirim.

| Field | Batas |
|---|---|
| `message` | wajib, maks 1000 karakter |
| `stack` | maks 20.000 karakter |
| `app_version` | maks 32 |
| `platform` | maks 16 |
| `device` | maks 128 |
| `os_version` | maks 32 |

`occurred_at` adalah waktu aplikasi gagal, bukan waktu laporannya terkirim. Aturannya sama dengan `submitted_at` (lihat §5): diperlakukan apa adanya kalau masuk akal, dan **dijepit** ke rentang wajar kalau jam HP-nya salah — tidak pernah ditolak. Kosongkan kalau tidak tahu; server memakai waktu terima.

**Tiga hal yang berbeda dari endpoint lain, dan semuanya disengaja:**

1. **Tidak butuh `Idempotency-Key`.** Ini satu-satunya POST yang tidak memerlukannya. Di sini pengulangan justru informasinya — lima ratus kejadian yang sama adalah fakta yang berbeda dari satu kejadian. Server menggabungkan yang identik sendiri, berdasar hash dari `message` + bagian atas `stack`, dan penggabungan itu bekerja lintas perangkat (yang tidak mungkin dilakukan kunci idempotensi).
2. **Batasnya lebih ketat: 12 per menit**, di atas batas 240 yang umum. Layar yang gagal di setiap rebuild bisa mengirim laporan secepat loop-nya berjalan, dan ledakan itu tidak boleh memakan jatah yang dibutuhkan outbox untuk mengirim laporan satu shift. Kalau kena 429 di sini, **buang saja** laporan yang tertahan — yang hilang cuma pengulangan, bukan penampakan pertama sesuatu yang baru.
3. **Tidak memerlukan akses modul.** Akun yang izinnya baru saja dicabut tetap bisa melapor — pencabutan itu sendiri adalah salah satu sebab crash yang masuk akal. Yang tetap diperlukan hanya token yang sah.

Penggabungannya memisahkan per **versi aplikasi**: kegagalan yang sama muncul lagi di versi baru menjadi baris tersendiri, bukan menambah hitungan baris lama. Itu disengaja — "bug yang sudah diperbaiki ternyata kembali" adalah hal terpenting yang bisa disampaikan tabel ini.

Sisi aplikasi disarankan **mengantre laporannya dulu di lokal** lalu mengirim saat ada jaringan: kegagalan justru sering terjadi saat sinyal buruk. Antreannya jangan memakai tabel outbox laporan petugas — aturannya berbeda, laporan crash boleh dibuang kalau menumpuk, laporan petugas tidak pernah boleh.

Hasilnya dibaca admin di `/admin` → **Sistem → Kegagalan Aplikasi**.

---

## 7. Katalog error

| Status | Bentuk | Artinya | Yang harus aplikasi lakukan |
|---|---|---|---|
| **400** | `{"message": ...}` | `Idempotency-Key` tidak ada atau tidak sah | Bug aplikasi. Perbaiki, jangan ulangi |
| **401** | `{"message": "Sesi Anda sudah berakhir. Masuk kembali."}` | Token mati, dicabut, atau akun dinonaktifkan | Hapus token tersimpan, kembali ke layar login. **Jangan hapus outbox** — laporannya masih valid setelah login ulang |
| **403** | `{"message": "Anda tidak punya akses ke bagian ini."}` | Tidak punya izin untuk modul itu | Segarkan `/me` dan sesuaikan menunya |
| **404** | `{"message": "Data yang diminta tidak ditemukan."}` | Id tidak ada | Jangan ulangi |
| **409** | `{"message": ...}` | Kunci idempotensi bentrok | Kalau "sedang diproses": tunggu lalu ulangi. Kalau "sudah dipakai untuk permintaan lain": bug aplikasi |
| **410** | `{"message": ...}` | Pos patroli sudah dinonaktifkan admin — lihat §6 | Jangan ulangi. Pindai ulang tidak akan pernah berhasil; tampilkan pesannya, minta satpam lapor ke pengawas |
| **422** | `{"message": ..., "errors": {...}}` | Data tidak lolos validasi | Tampilkan pesannya. **Boleh dikirim ulang dengan kunci yang sama** setelah diperbaiki |
| **429** | `{"message": ...}` | Terlalu banyak percobaan | Tunggu, lalu ulangi dengan jeda menaik |
| **5xx** | `{"message": "Terjadi kesalahan di server. Coba lagi nanti."}` | Masalah server | Ulangi dengan jeda menaik. Pertahankan di outbox |

Bentuk 422:

```json
{
  "message": "Area wajib dipilih. (and 1 more error)",
  "errors": {
    "ob_area_id": ["Area wajib dipilih."],
    "photo_ids": ["Laporan wajib menyertakan foto."]
  }
}
```

Tampilkan pesan dari `errors` di bawah field masing-masing. `message` di tingkat atas hanya ringkasan dan **memuat teks Inggris "(and N more errors)"** — jangan tampilkan apa adanya ke petugas.

**Semua pesan `message` sudah berbahasa Indonesia dan aman ditampilkan langsung.** Ini aturan keras di NADI: teks teknis mentah tidak pernah sampai ke layar. Kalau menemukan pesan yang terasa seperti pesan programer, itu bug — laporkan.

### Batas percobaan

| Cakupan | Batas |
|---|---|
| `POST /auth/login` | **5 per menit** per email + IP |
| `POST /crash` | **12 per menit** per token, di atas batas 240 di bawah |
| Semua endpoint lain (per token) | **240 per menit** |

Angka 240 itu longgar dengan sengaja. Outbox yang pulang dari basement membawa dua belas laporan beserta fotonya — sekitar 50 request dalam beberapa detik — dan batas konvensional 60/menit justru akan mencekik kasus yang seluruh rancangan offline ini dibuat untuk melayaninya. Ini pagar untuk klien yang lepas kendali, bukan kuota pemakaian. Kalau batas ini pernah kena saat pengiriman normal, berarti angkanya salah — laporkan.

429 menyertakan header **`Retry-After`** (detik). Pakai itu alih-alih menebak jeda sendiri.

**Tidak ada kuota unggah.** Tidak ada batas jumlah foto per petugas per hari, dan tidak ada batas total penyimpanan. Yang ada hanya batas per berkas (10 MB) dan pembersihan foto yatim setelah 7 hari.

### Aturan mengulang

| Status | Ulangi otomatis? |
|---|---|
| 408, 429, 5xx, timeout jaringan | Ya, jeda menaik: 1s, 2s, 4s, … maksimal ~5 menit |
| 409 "sedang diproses" | Ya, setelah beberapa detik |
| 400, 401, 403, 404, 410, 422 | **Tidak.** Perlu tindakan pengguna atau perbaikan aplikasi |

---

## 8. Perilaku offline per modul

Tidak semua langkah sama amannya untuk diantre. Tabel ini adalah aturan yang harus ditegakkan aplikasi.

| Langkah | Boleh offline? | Alasan |
|---|---|---|
| Kirim laporan OB | **Ya** | Tidak ada penguncian, tidak ada nomor urut, tidak ada tabrakan. Basement dan toilet memang tidak ada sinyal |
| Kirim laporan Patroli Security | **Ya** | Sama. Tangga darurat dan area parkir justru tempat kerjanya |
| Kirim laporan Inspeksi HK | **Ya** | Sama, asalkan pohon kategori sudah di-cache supaya form bersyaratnya bisa dirender |
| Unggah foto | **Ya** | Tiap foto yang berhasil tersimpan sebagai kemajuan |
| Tandai berangkat (`transit`) | **Ya** | Statusnya milik kurir itu sendiri, tidak diperebutkan |
| Tandai terkirim (`deliver`) | **Ya** | Sama |
| **Ambil tugas (`claim`)** | **TIDAK** | Diperebutkan. Klaim offline terlihat berhasil di HP lalu kalah diam-diam |
| Laporan kegagalan (`crash`) | **Ya**, tapi antreannya terpisah | Kegagalan justru sering terjadi saat sinyal buruk. Antreannya sendiri, bukan outbox laporan petugas: laporan crash boleh dibuang kalau menumpuk, laporan petugas tidak pernah boleh |

Untuk yang boleh offline, alurnya selalu sama:

1. Simpan ke outbox lokal **dan beri tahu petugas laporannya tersimpan** — jangan pernah membuat orang menunggu jaringan
2. Buat UUID sekali, simpan bersama laporannya
3. Saat ada sinyal: unggah foto yang belum terunggah, simpan `photo_ids` yang berhasil, lalu kirim laporannya dengan UUID yang sama
4. Hapus dari outbox hanya setelah 2xx

### Contoh alur kirim ulang

```dart
Future<bool> kirim(LaporanTertunda laporan) async {
  // Foto yang sudah berhasil TIDAK diunggah ulang.
  for (final path in laporan.fotoBelumTerunggah) {
    final id = await api.unggahFoto(path);      // punya Idempotency-Key sendiri
    laporan.photoIds.add(id);
    laporan.fotoBelumTerunggah.remove(path);
    await db.simpan(laporan);                   // simpan kemajuannya SEKARANG
  }

  try {
    await api.kirimLaporan(
      modul: laporan.modul,
      payload: {...laporan.payload, 'photo_ids': laporan.photoIds},
      // UUID yang sama di setiap percobaan. Ini yang membuat kirim ulang aman.
      idempotencyKey: laporan.idempotencyKey,
    );
    await db.hapus(laporan);
    return true;
  } on ApiException catch (e) {
    if (e.status == 422 || e.status == 403 || e.status == 400) {
      laporan.status = 'gagal';                 // butuh tindakan pengguna
      await db.simpan(laporan);
      return false;
    }
    return false;                               // 5xx / timeout — coba lagi nanti
  }
}
```

Tiga kesalahan yang paling mudah terjadi, dan semuanya senyap:

1. **Membuat UUID baru saat kirim ulang.** Hasilnya laporan dobel di panel admin. UUID dibuat sekali, saat laporan dibuat
2. **Mengunggah ulang foto yang sudah berhasil.** Menghabiskan kuota dan waktu justru saat sinyalnya paling buruk
3. **Menghapus outbox saat kena 401.** Token mati bukan berarti laporannya tidak valid — hapus tokennya, pertahankan laporannya

---

## 9. Yang sengaja tidak ada di API

Bukan karena belum sempat, tapi karena memang tidak seharusnya ada di HP:

| Tidak ada | Alasan |
|---|---|
| **POS Tiket Event & Bazar** | Butuh printer struk 80mm dan laci kasir. Dikerjakan di meja, bukan sambil berjalan |
| **Operator Antrian** | Layar meja loket yang butuh koneksi stabil dan siaran real-time |
| **Booking Ruangan** | Pengecekan bentrok jadwal harus dilakukan server terhadap data terkini; kalender di layar besar jauh lebih berguna |
| **Semua fungsi `/admin`** | Kelola pengguna, master data, laporan, pengaturan — pekerjaan admin di depan layar lebar |
| **Push notification** | Belum ada infrastrukturnya sama sekali. Untuk v1, pakai tarik-untuk-segarkan |

Kalau ada kebutuhan yang jatuh ke daftar ini, bicarakan dulu — bukan menambah endpoint diam-diam.

---

## 10. Menjalankan backend lokal

```bash
git clone <repo> && cd nadi
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

Akun admin dibuat oleh seeder; passwordnya bisa diatur dengan:

```bash
php artisan nadi:admin-password admin@nadi.test --generate
```

Untuk membuat akun uji lapangan, masuk ke `/admin` → Pengguna → buat akun baru → beri role `ob`.

### Uji cepat dengan curl

```bash
BASE=http://localhost:8000/api/v1

TOKEN=$(curl -s -X POST $BASE/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"budi@tangcity.com","password":"rahasia","device_name":"curl"}' \
  | jq -r .token)

curl -s $BASE/me -H "Authorization: Bearer $TOKEN" | jq
curl -s $BASE/ob/areas -H "Authorization: Bearer $TOKEN" | jq

FOTO=$(curl -s -X POST $BASE/uploads \
  -H "Authorization: Bearer $TOKEN" \
  -H "Idempotency-Key: $(uuidgen)" \
  -F photo=@bukti.jpg | jq -r .data.id)

KUNCI=$(uuidgen)
curl -s -X POST $BASE/ob/checklists \
  -H "Authorization: Bearer $TOKEN" \
  -H "Idempotency-Key: $KUNCI" \
  -H 'Content-Type: application/json' \
  -d "{\"ob_area_id\":1,\"photo_ids\":[\"$FOTO\"],\"notes\":\"uji coba\"}" | jq

# Kirim ulang dengan kunci yang sama — balasannya identik, dan tidak ada
# laporan kedua yang terbuat. Inilah yang membuat outbox aman.
curl -s -X POST $BASE/ob/checklists \
  -H "Authorization: Bearer $TOKEN" \
  -H "Idempotency-Key: $KUNCI" \
  -H 'Content-Type: application/json' \
  -d "{\"ob_area_id\":1,\"photo_ids\":[\"$FOTO\"],\"notes\":\"uji coba\"}" -i | head -20
```

### Kalau perlu memastikan perilaku sebenarnya

Test backend adalah spesifikasi yang bisa dijalankan, dan ditulis sebagai kalimat penuh. Kalau dokumen ini terasa ambigu, jawabannya ada di sana:

```
tests/Feature/Api/AuthTest.php                 login, 2FA, batas percobaan, logout
tests/Feature/Api/IdempotencyTest.php          seluruh kontrak kunci idempotensi
tests/Feature/Api/UploadTest.php               batas foto dan kepemilikan
tests/Feature/Api/MobileAccessTest.php         siapa boleh masuk, kapan token dicabut
tests/Feature/Api/ObChecklistApiTest.php       modul OB dari ujung ke ujung
tests/Feature/Api/SecurityPatrolApiTest.php    resolusi kode QR, dan bukti tidak ada endpoint daftar pos
tests/Feature/Api/HkInspectionApiTest.php      field bersyarat, penurunan kategori, job Telegram
tests/Feature/Api/MessengerTaskApiTest.php     alur status dan konflik klaim
```

Jalankan dengan `php artisan test tests/Feature/Api`.

---

## 11. Daftar periksa sebelum rilis

- [ ] Token disimpan di `flutter_secure_storage`, bukan `SharedPreferences`
- [ ] Menu utama dibangun dari `user.modules`, bukan daftar tetap
- [ ] `/me` dipanggil setiap aplikasi dibuka
- [ ] Setiap laporan punya satu UUID yang **dibuat sekali** dan dipakai ulang di setiap percobaan kirim
- [ ] Outbox menyimpan laporan **sebelum** mencoba mengirim
- [ ] `photo_ids` yang sudah berhasil disimpan, tidak diunggah ulang
- [ ] Foto dikompres di HP sebelum diunggah (batas 10 MB)
- [ ] `submitted_at` diisi waktu petugas menekan Kirim, bukan waktu terkirim
- [ ] `submitted_at` dikirim **dengan penanda zona** (`Z` atau `+07:00`), bukan tanpa offset
- [ ] URL foto tidak pernah disimpan (berumur 30 menit)
- [ ] 401 menghapus token tapi **tidak** menghapus outbox
- [ ] Pengulangan otomatis hanya untuk 408/429/5xx/timeout
- [ ] Master data (area, pos, kategori HK) di-cache untuk mode offline
- [ ] Pengambilan tugas Messenger diwajibkan online
- [ ] Kegagalan tak tertangani dikirim ke `POST /crash`, lewat antrean yang **terpisah** dari outbox laporan
- [ ] Blok `app` yang tidak ada diperlakukan sebagai "tidak ada pembaruan", bukan error
