<?php

use App\Support\SocialLinks;
use Illuminate\Support\Facades\Route;

/*
 * The only two pages in NADI that a signed-out stranger may read.
 *
 * They exist because Google will not let an OAuth app leave "Testing" without
 * a published privacy policy and terms of use — and an app stuck in Testing is
 * issued refresh tokens that expire after seven days, which is exactly how the
 * Google Drive backup died unnoticed. Registered outside the auth middleware
 * on purpose: Google's reviewers and any employee must be able to open them
 * without an account.
 */
Route::view('kebijakan-privasi', 'legal.privacy', ['contact' => SocialLinks::EMAIL])
    ->name('legal.privacy');

Route::view('syarat-ketentuan', 'legal.terms', ['contact' => SocialLinks::EMAIL])
    ->name('legal.terms');
