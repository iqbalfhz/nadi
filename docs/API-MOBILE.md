# API Mobile NADI — Panduan untuk Programer Flutter

Dokumen ini ditujukan untuk orang yang akan membangun aplikasi Flutter-nya dan belum pernah melihat kode backend NADI. Tidak perlu membaca kode PHP mana pun untuk mengikuti dokumen ini.

Contoh request dan respons di bawah **disalin dari server yang benar-benar berjalan**, bukan dikarang.

Terakhir diperbarui: 3 September 2026 · Base URL produksi diberikan terpisah · Semua endpoint diawali `/api/v1`

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

**Yang sudah bisa dipanggil hari ini: Checklist OB.** Tiga modul lainnya menyusul dengan pola yang persis sama — lihat [bagian 8](#8-modul-yang-belum-ada).

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

**Setiap POST ke `/api/v1` wajib membawa header `Idempotency-Key` berisi UUID v4.**

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

### Waktu

Semua waktu ISO 8601 dengan offset, zona **Asia/Jakarta**: `2026-09-03T14:23:57+07:00`.

---

## 6. Referensi endpoint

Semua endpoint kecuali `auth/login` butuh header `Authorization: Bearer <token>`.
Semua POST butuh header `Idempotency-Key`.

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
    "modules": ["ob"]
  }
}
```

`locale` bernilai `null`, `"id"`, atau `"en"` — bahasa pilihan pengguna di web. `null` berarti mengikuti bahasa aplikasi (Indonesia). Pesan error dari server mengikuti pilihan ini.

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

---

## 7. Katalog error

| Status | Bentuk | Artinya | Yang harus aplikasi lakukan |
|---|---|---|---|
| **400** | `{"message": ...}` | `Idempotency-Key` tidak ada atau tidak sah | Bug aplikasi. Perbaiki, jangan ulangi |
| **401** | `{"message": "Sesi Anda sudah berakhir. Masuk kembali."}` | Token mati, dicabut, atau akun dinonaktifkan | Hapus token tersimpan, kembali ke layar login. **Jangan hapus outbox** — laporannya masih valid setelah login ulang |
| **403** | `{"message": "Anda tidak punya akses ke bagian ini."}` | Tidak punya izin untuk modul itu | Segarkan `/me` dan sesuaikan menunya |
| **404** | `{"message": "Data yang diminta tidak ditemukan."}` | Id tidak ada | Jangan ulangi |
| **409** | `{"message": ...}` | Kunci idempotensi bentrok | Kalau "sedang diproses": tunggu lalu ulangi. Kalau "sudah dipakai untuk permintaan lain": bug aplikasi |
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

### Aturan mengulang

| Status | Ulangi otomatis? |
|---|---|
| 408, 429, 5xx, timeout jaringan | Ya, jeda menaik: 1s, 2s, 4s, … maksimal ~5 menit |
| 409 "sedang diproses" | Ya, setelah beberapa detik |
| 400, 401, 403, 404, 422 | **Tidak.** Perlu tindakan pengguna atau perbaikan aplikasi |

---

## 8. Modul yang belum ada

Tiga modul lain belum punya endpoint, tapi datanya sudah ada di sistem dan bentuknya sudah pasti. Semuanya akan mengikuti pola Checklist OB persis: `GET` master data → `POST /uploads` per foto → `POST` laporannya dengan `photo_ids`, `submitted_at`, dan `Idempotency-Key`.

### Patroli Security (`security`)

Satpam memindai QR yang tertempel di pos. **Ini kandidat terkuat untuk `mobile_scanner`** — di web, satpam harus membuka aplikasi kamera bawaan yang lalu melempar ke browser. Pemindai di dalam aplikasi menghapus lompatan itu.

QR-nya memuat URL yang mengandung kode acak 32 karakter. Ambil kodenya, cocokkan dengan daftar pos yang di-cache, lalu tampilkan formnya.

Field: `checkpoint_code` (dari QR), `photos` (wajib, boleh banyak), `incident_report` (opsional, maks 1000).

Tahan offline: **ya**, asalkan daftar pos di-cache supaya pemindaian bisa dikenali tanpa jaringan.

### Inspeksi HK (`hk`)

Paling rumit dari keempatnya. Pengawas memilih **Kategori → Titik** bertingkat (pilihan titik menyempit sesuai kategori), lalu shift, kondisi, foto, dan catatan.

Dua field muncul bersyarat:

- **Lantai** — hanya untuk kategori yang ditandai `requires_floor` di master data
- **Tindak Lanjut** — **wajib** bila kondisi bukan "Bersih". Ini disengaja: pengawas tidak boleh melapor "Kotor" lalu pergi tanpa menyebutkan apa yang dilakukan

Kondisi: `bersih` / `perlu_perbaikan` / `kotor`. Shift: `pagi` / `siang` / `malam`.

Cache kategori dan titiknya (±90 titik) supaya form bersyaratnya bisa dirender offline. Server tetap menurunkan ulang kategorinya dari titik yang dipilih — jangan kirim kategori sebagai field terpisah.

Laporan HK juga dikirim otomatis ke grup Telegram oleh server. Aplikasi tidak perlu melakukan apa pun untuk itu.

### Tugas Messenger (`messenger`)

Satu-satunya modul dengan alur status:

```
tersedia → diambil → dalam perjalanan → terkirim
```

Kurir melihat daftar tugas terbuka (milik semua orang, bukan hanya dirinya), mengambil satu, menandai berangkat, lalu menandai terkirim dengan **satu foto bukti**.

**Pengambilan tugas tidak boleh di-antre offline.** Ini rebutan: dua kurir bisa menekan "Ambil" pada tugas yang sama, dan server menyelesaikannya dengan penguncian baris. Pengambilan offline akan kalah diam-diam — kurir mengira dapat tugas, padahal tidak. Wajibkan online untuk langkah ini; sisanya boleh di-antre.

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
tests/Feature/Api/AuthTest.php             login, 2FA, batas percobaan, logout
tests/Feature/Api/IdempotencyTest.php      seluruh kontrak kunci idempotensi
tests/Feature/Api/UploadTest.php           batas foto dan kepemilikan
tests/Feature/Api/ObChecklistApiTest.php   modul OB dari ujung ke ujung
tests/Feature/Api/MobileAccessTest.php     siapa boleh masuk, kapan token dicabut
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
- [ ] URL foto tidak pernah disimpan (berumur 30 menit)
- [ ] 401 menghapus token tapi **tidak** menghapus outbox
- [ ] Pengulangan otomatis hanya untuk 408/429/5xx/timeout
- [ ] Master data (area, pos, kategori HK) di-cache untuk mode offline
- [ ] Pengambilan tugas Messenger diwajibkan online
