<x-legal-layout title="Kebijakan Privasi">
    <p class="lead">
        NADI adalah sistem operasional internal kantor. Halaman ini menjelaskan
        data apa yang disimpan, untuk apa dipakai, dan siapa yang bisa melihatnya.
    </p>

    <h2>Siapa yang memakai NADI</h2>
    <p>
        NADI hanya dipakai oleh karyawan yang akunnya dibuatkan oleh administrator.
        Tidak ada pendaftaran mandiri, dan tidak ada bagian dari sistem ini yang
        terbuka untuk umum selain halaman yang sedang Anda baca.
    </p>

    <h2>Data yang disimpan</h2>
    <ul>
        <li><strong>Akun:</strong> nama, alamat email, dan kata sandi yang disimpan dalam bentuk teracak (hash), bukan teks asli.</li>
        <li><strong>Aktivitas kerja:</strong> checklist kebersihan, patroli security, pengantaran dokumen, booking ruangan, antrian, penjualan tiket dan bazar.</li>
        <li><strong>Foto bukti kerja</strong> yang diunggah petugas dan pengawas.</li>
        <li><strong>Riwayat aktivitas:</strong> waktu login, perubahan data, upaya akses yang ditolak, beserta alamat IP.</li>
    </ul>

    <h2>Untuk apa dipakai</h2>
    <p>
        Seluruh data dipakai untuk menjalankan operasional kantor: mencatat pekerjaan
        yang sudah dilakukan, menyusun laporan, dan menelusuri kembali kalau terjadi
        kekeliruan. <strong>NADI tidak menjual, menyewakan, atau membagikan data ini
        kepada pihak ketiga mana pun untuk keperluan pemasaran.</strong>
    </p>

    <h2>Akses ke Google Drive</h2>
    <div class="callout">
        Bila fitur backup dinyalakan, NADI meminta izin ke Google Drive milik
        administrator untuk <strong>membuat dan menyimpan berkas cadangan</strong> di
        satu folder yang ditentukan sendiri oleh administrator.
    </div>
    <p>
        Izin itu dipakai <strong>hanya</strong> untuk menulis berkas cadangan tersebut.
        NADI tidak membaca, mengubah, atau menghapus berkas lain di Google Drive, dan
        tidak mengirimkan isi Drive ke mana pun. Izinnya bisa dicabut kapan saja oleh
        administrator melalui halaman Izin Akun Google, atau dengan mematikan fitur
        backup di dalam NADI.
    </p>

    <h2>Notifikasi Telegram</h2>
    <p>
        Laporan Checklist HK dikirim otomatis ke satu grup Telegram yang ditentukan
        administrator. Isi yang dikirim adalah data laporan itu sendiri: kategori,
        titik, kondisi, shift, nama petugas dan pengawas, keterangan, serta fotonya.
    </p>

    <h2>Keamanan</h2>
    <ul>
        <li>Seluruh akses berjalan lewat sambungan terenkripsi (HTTPS).</li>
        <li>Foto bukti kerja disimpan di penyimpanan tertutup dan hanya bisa dibuka lewat tautan bertanda tangan yang kedaluwarsa, bukan lewat alamat yang bisa ditebak.</li>
        <li>Kredensial pihak ketiga disimpan dalam keadaan terenkripsi di database.</li>
        <li>Setiap karyawan hanya melihat menu dan data sesuai perannya.</li>
    </ul>

    <h2>Berapa lama disimpan</h2>
    <p>
        Data operasional disimpan selama masih dibutuhkan untuk keperluan kantor.
        Riwayat aktivitas dibersihkan otomatis setelah <strong>365 hari</strong>.
    </p>

    <h2>Hak Anda</h2>
    <p>
        Karyawan berhak menanyakan data apa yang tersimpan atas namanya, meminta
        perbaikan data yang keliru, dan meminta penjelasan atas pencatatan yang
        dilakukan sistem. Sampaikan melalui administrator atau kontak di bawah.
    </p>

    <h2>Kontak</h2>
    <p>
        Pertanyaan mengenai kebijakan ini bisa dikirim ke
        <a href="mailto:{{ $contact }}">{{ $contact }}</a>.
    </p>
</x-legal-layout>
