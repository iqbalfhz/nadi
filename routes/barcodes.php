<?php

use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The printable image for a generated barcode/QR (see Barcode::renderPng()).
Route::get('barcodes/{barcode}/download', function (Request $request, Barcode $barcode) {
    abort_unless($request->user()->can('view', $barcode), 403);

    return response($barcode->renderPng(), 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'attachment; filename="'.$barcode->format->value.'-'.$barcode->id.'.png"',
    ]);
})->middleware(['auth'])->name('barcodes.download');
