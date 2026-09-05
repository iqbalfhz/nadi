# Catatan Perubahan API Mobile

Setiap perubahan pada `/api/v1`, terbaru di atas. Untuk programer Flutter: **baca dari atas sampai menemukan tanggal terakhir yang sudah Anda kerjakan**, lalu berhenti.

Rujukan lengkapnya tetap di [API-MOBILE.md](API-MOBILE.md); berkas ini hanya menyebutkan apa yang **berubah** dan apa yang harus dilakukan aplikasi.

Label yang dipakai:

| Label | Artinya |
|---|---|
| **WAJIB** | Aplikasi rusak atau salah kalau tidak menyesuaikan |
| **DISARANKAN** | Aplikasi tetap jalan, tapi ada cara yang lebih baik sekarang |
| **INFO** | Tidak ada yang perlu dikerjakan |

---

## 5 September 2026

Dari dua temuan pengujian di HP fisik ([B6 dan B7](PERTANYAAN-BACKEND.md)).

### WAJIB — Kirim hasil pindai QR apa adanya

Stiker patroli memuat **URL**, bukan kode telanjang:

```
https://<domain>/app/security-scan/<kode 32 karakter alfanumerik>
```

Bentuk itu disengaja dan tidak akan diubah — satpam tanpa aplikasi mengarahkan kamera bawaan ke stiker yang sama dan mendarat di formulir web. Tapi aplikasi **tidak perlu lagi mem-parsingnya**.

**Endpoint baru:**

```
GET /api/v1/security/scan?scanned=<hasil pindai mentah, URL-encoded>
```

Menerima URL penuh, URL dengan query, URL dengan slash penutup, dan kode telanjang. Balasannya sama persis dengan `/security/checkpoints/{code}`. `scanned` kosong → **400**.

**`POST /security/patrols` juga menerima hasil pindai mentah** di `checkpoint_code`. Ini yang paling penting untuk outbox: patroli yang mengantre di basement membawa hasil pindai apa adanya, dan sebelumnya akan gagal validasi berjam-jam kemudian — jauh setelah satpamnya meninggalkan pos.

**Yang harus dikerjakan:**

1. Pindah ke `GET /security/scan?scanned=`
2. Kirim hasil pindai mentah juga sebagai `checkpoint_code` saat submit
3. `kodeDariHasilPindai()` boleh dihapus — atau ditahan sebagai sabuk pengaman, kode telanjang tetap diterima

Kenapa ini lebih baik daripada parsing di HP: format stiker itu milik backend. Selama parsingnya di aplikasi, mengubah pola URL akan mematikan setiap handset yang belum di-update. Sekarang parsingnya ada di kelas yang menghasilkan stikernya, jadi format dan pembacanya tidak bisa berpisah.

`GET /security/checkpoints/{code}` masih berfungsi untuk kode telanjang, tapi tidak bisa menerima URL — slash di dalamnya membuat request mendarat di rute lain, atau tidak terkirim sama sekali.

### WAJIB — Tugas Messenger sekarang menyebut asal dan pemohon

Dua field baru di **semua** balasan Messenger (`open`, `mine`, `claim`, `transit`, `deliver`):

```json
{
  "origin": "Front Office Lt 1",
  "destination": "Kantor Manajemen Lt 5",
  "requester": { "name": "Sinta", "department": "HRD" }
}
```

- **`origin`** — tempat kurir **mengambil** dokumennya. Tanpa ini kurir tahu tujuan tapi tidak tahu harus ke mana lebih dulu
- **`requester`** — pemohonnya, untuk ditanya kalau dokumennya tidak ada di tempat. `department` bisa `null`

**Yang harus dikerjakan:** tampilkan `origin` di kartu tugas, sebaiknya **sebelum** `destination` — urutan yang dibaca kurir adalah ambil dulu, antar kemudian.

`origin` bisa `null` hanya untuk permintaan yang dibuat sebelum kolomnya ada; permintaan baru mewajibkannya di formulir web.

### INFO — Pesan konflik klaim bertambah satu

Sudah disebut di jawaban B3, diulang di sini karena mudah terlewat: `POST /messenger/tasks/{id}/claim` bisa menjawab **409** dengan dua pesan berbeda.

```json
{ "message": "Tugas ini sudah diambil messenger lain." }
{ "message": "Tugas ini sudah Anda ambil. Ada di daftar Tugas Saya." }
```

Keduanya aman ditampilkan apa adanya.

### INFO — Sisi web ikut diperbaiki

Halaman kurir di `/app` juga hanya menampilkan tujuan. Sekarang menampilkan asal dan pemohon seperti aplikasi.

Ini disebutkan bukan karena memengaruhi Flutter, tapi supaya jelas bahwa lubangnya ada **di modulnya**, bukan di API. Aplikasi hanya tempat lubang itu akhirnya terlihat.

---

## 4 September 2026

Dari jawaban atas [sebelas pertanyaan pertama](PERTANYAAN-BACKEND.md).

### WAJIB — Dua sebab 404 pos dipisah

`GET /security/checkpoints/{code}` dan `/security/scan` sekarang membedakan:

| Status | Artinya | Tindakan satpam |
|---|---|---|
| **404** | Kode tidak dikenali | Pindai ulang |
| **410** | Pos sudah dinonaktifkan admin | Pindai ulang tidak akan pernah berhasil. Laporkan ke pengawas |

**Yang harus dikerjakan:** tangani 410 sebagai penolakan permanen, jangan diulang otomatis. Tampilkan satu tindakan sesuai sebabnya.

### DISARANKAN — Batas percobaan

| Cakupan | Batas |
|---|---|
| `POST /auth/login` | 5 per menit per email + IP |
| Semua endpoint lain (per token) | 240 per menit |

Sebelumnya tidak ada batas sama sekali. 240 sengaja longgar: outbox yang pulang dari basement membawa dua belas laporan beserta fotonya ≈ 50 request dalam beberapa detik.

**429 menyertakan `Retry-After`** (detik) — pakai itu alih-alih menebak jeda sendiri.

Kalau batas ini pernah kena saat pengiriman normal, angkanya salah. Laporkan, jangan disiasati di aplikasi.

### INFO — Yang dikonfirmasi, tanpa perubahan kode

- `auth/login` **tidak** butuh `Idempotency-Key`. Dokumen §3 yang terlalu menyamaratakan
- **Keempat endpoint foto selalu mengembalikan array**, termasuk `/messenger/tasks/{id}/proof` yang koleksinya `singleFile`. Kosong = `[]`, tidak pernah objek tunggal
- `GET /hk/inspections` dan `/hk/inspections/{id}` **sudah ada sejak awal**, hanya luput dari dokumen
- **Master data tidak pernah berhalaman** (`/ob/areas`, `/hk/categories`). Aman di-cache seluruhnya
- `code` pos **dijamin alfanumerik** 32 karakter
- **Tidak ada kuota unggah** per pengguna atau total. Hanya 10 MB per berkas dan pembersihan foto yatim 7 hari

---

## 3 September 2026

Rilis pertama API. Empat modul lapangan: Checklist OB, Patroli Security, Inspeksi HK, Tugas Messenger.

Lihat [API-MOBILE.md](API-MOBILE.md) untuk kontrak lengkapnya.

---

## Cara kerja ke depan

Setiap perubahan API akan ditulis di sini lebih dulu, dengan label WAJIB / DISARANKAN / INFO. Yang berlabel **WAJIB** tidak akan pernah dikirim tanpa masa peralihan — bentuk lama tetap berfungsi sampai Anda sempat pindah.

Kalau menemukan sesuatu yang tidak sesuai dokumen, tulis dengan format yang sudah Anda pakai di [PERTANYAAN-BACKEND.md](PERTANYAAN-BACKEND.md): apa yang tertulis, apa yang benar-benar terjadi, dan apa akibatnya bagi aplikasi. Lima dari tiga belas butir sejauh ini menghasilkan perubahan backend justru karena ditulis seperti itu.
