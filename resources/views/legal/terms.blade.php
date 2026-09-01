<x-legal-layout title="Syarat & Ketentuan">
    <p class="lead">
        NADI adalah perkakas kerja internal. Dengan memakai akun yang diberikan,
        Anda menyetujui ketentuan berikut.
    </p>

    <h2>Penggunaan akun</h2>
    <ul>
        <li>Akun bersifat pribadi dan tidak boleh dipakai bergantian dengan orang lain.</li>
        <li>Jaga kerahasiaan kata sandi. Segala tindakan yang tercatat atas nama sebuah akun dianggap dilakukan oleh pemiliknya.</li>
        <li>Kalau Anda menduga akun Anda dipakai orang lain, segera laporkan ke administrator.</li>
    </ul>

    <h2>Isi yang Anda kirimkan</h2>
    <ul>
        <li>Laporan dan foto yang diunggah harus benar dan berkaitan dengan pekerjaan.</li>
        <li>Jangan mengunggah data pribadi orang lain yang tidak diperlukan untuk pekerjaan itu.</li>
        <li>Laporan yang sudah dikirim tidak bisa diubah sendiri — itu disengaja, supaya catatan tetap dapat dipercaya. Kalau ada kekeliruan, hubungi administrator.</li>
    </ul>

    <h2>Pencatatan</h2>
    <p>
        Kegiatan di dalam sistem dicatat, termasuk waktu login, perubahan data, dan
        upaya membuka halaman yang tidak diizinkan. Pencatatan ini bertujuan menjaga
        keamanan dan menelusuri kekeliruan — bukan mengawasi hal di luar pekerjaan.
        Rinciannya ada di <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>.
    </p>

    <h2>Ketersediaan</h2>
    <p>
        NADI bisa berhenti sementara untuk pemeliharaan atau pembaruan. Kami berusaha
        menjaga data tetap aman lewat pencadangan berkala, tetapi tidak menjanjikan
        layanan berjalan tanpa henti.
    </p>

    <h2>Penghentian akses</h2>
    <p>
        Administrator dapat menonaktifkan akun yang sudah tidak lagi bekerja di kantor
        ini, atau yang dipakai dengan cara yang melanggar ketentuan di atas.
    </p>

    <h2>Kontak</h2>
    <p>
        Pertanyaan mengenai ketentuan ini bisa dikirim ke
        <a href="mailto:{{ $contact }}">{{ $contact }}</a>.
    </p>
</x-legal-layout>
