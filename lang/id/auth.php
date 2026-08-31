<?php

/**
 * Laravel only ships English here, so a failed login used to read "These
 * credentials do not match our records." — the kind of sentence that makes
 * an office user think something is broken rather than that they mistyped.
 */
return [
    'failed' => 'Email atau password salah.',
    'password' => 'Password yang Anda masukkan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam :seconds detik.',
];
