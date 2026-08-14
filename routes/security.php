<?php

use App\Models\SecurityCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin-only: the printable QR code for a checkpoint, encoding the scan URL
// that a guard's phone camera opens directly (see SecurityCheckpoint::qrCodePng()).
Route::get('security-checkpoints/{securityCheckpoint}/qr-code', function (Request $request, SecurityCheckpoint $securityCheckpoint) {
    abort_unless($request->user()->can('view', $securityCheckpoint), 403);

    return response($securityCheckpoint->qrCodePng(), 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'attachment; filename="qr-'.$securityCheckpoint->code.'.png"',
    ]);
})->middleware(['auth'])->name('security-checkpoints.qr-code');
