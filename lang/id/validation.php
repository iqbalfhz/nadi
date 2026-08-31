<?php

/**
 * Laravel only ships English validation messages, so every mistyped form used
 * to answer with things like "The pB1 (%) field is required." — English, and
 * with the label mangled on the way through.
 *
 * Only the rules this application actually uses are translated in full; the
 * rest keep Laravel's wording so nothing is silently blank if a new rule is
 * introduced. Add to this file rather than reaching for a translation package:
 * these strings are read by the office every day and are worth wording
 * deliberately.
 */
return [
    'accepted' => ':attribute harus disetujui.',
    'active_url' => ':attribute bukan alamat web yang valid.',
    'after' => ':attribute harus setelah :date.',
    'after_or_equal' => ':attribute harus sama dengan atau setelah :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus sebelum :date.',
    'before_or_equal' => ':attribute harus sama dengan atau sebelum :date.',
    'between' => [
        'array' => ':attribute harus berisi antara :min sampai :max item.',
        'file' => 'Ukuran :attribute harus antara :min sampai :max kilobyte.',
        'numeric' => ':attribute harus antara :min sampai :max.',
        'string' => ':attribute harus antara :min sampai :max karakter.',
    ],
    'boolean' => ':attribute hanya bisa bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Password yang Anda masukkan salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus tanggal :date.',
    'date_format' => 'Format :attribute tidak sesuai.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
    'dimensions' => 'Ukuran gambar :attribute tidak sesuai.',
    'distinct' => ':attribute sudah dipilih sebelumnya.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan salah satu dari: :values.',
    'exists' => ':attribute yang dipilih tidak tersedia.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute wajib diisi.',
    'gt' => [
        'array' => ':attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value kilobyte.',
        'numeric' => ':attribute harus lebih dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute harus berisi :value item atau lebih.',
        'file' => 'Ukuran :attribute minimal :value kilobyte.',
        'numeric' => ':attribute minimal :value.',
        'string' => ':attribute minimal :value karakter.',
    ],
    'image' => ':attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak tersedia.',
    'integer' => ':attribute harus berupa angka bulat.',
    'lt' => [
        'array' => ':attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value kilobyte.',
        'numeric' => ':attribute harus kurang dari :value.',
        'string' => ':attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute tidak boleh lebih dari :value item.',
        'file' => 'Ukuran :attribute maksimal :value kilobyte.',
        'numeric' => ':attribute maksimal :value.',
        'string' => ':attribute maksimal :value karakter.',
    ],
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => ':attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => ':attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => ':attribute harus berisi minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'not_in' => 'Pilihan :attribute tidak tersedia.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => [
        'letters' => ':attribute harus mengandung minimal satu huruf.',
        'mixed' => ':attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus mengandung minimal satu angka.',
        'symbols' => ':attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute pernah bocor di kebocoran data. Silakan pakai password lain.',
    ],
    'present' => ':attribute wajib ada.',
    'prohibited' => ':attribute tidak boleh diisi.',
    'regex' => 'Format :attribute tidak sesuai.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi jika :other bernilai :value.',
    'required_unless' => ':attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => ':attribute wajib diisi jika ada :values.',
    'required_without' => ':attribute wajib diisi jika tidak ada :values.',
    'same' => ':attribute dan :other harus sama.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus :size karakter.',
    ],
    'starts_with' => ':attribute harus diawali dengan salah satu dari: :values.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai. Silakan pakai yang lain.',
    'uploaded' => ':attribute gagal diunggah. Cek ukuran berkasnya, lalu coba lagi.',
    'url' => ':attribute harus berupa alamat web yang lengkap, diawali http:// atau https://.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Filament passes each field's own label through as the attribute name, so
    | these are only needed for the few places validation runs outside a
    | Filament form — the Fortify login and password screens.
    |
    */

    'attributes' => [
        'email' => 'Email',
        'password' => 'Password',
        'current_password' => 'Password saat ini',
        'name' => 'Nama',
        'code' => 'Kode',
    ],
];
