<?php

use App\Livewire\Queue\Display;
use App\Livewire\Queue\KioskPinGate;
use App\Livewire\Queue\KioskTake;
use Illuminate\Support\Facades\Route;

// Public routes for the Antrian (queue) module — no login, since visitors and
// the waiting-room display don't have NADI accounts. See EnsureKioskIsUnlocked
// for why the ticket-taking kiosk still needs its own PIN gate.
Route::get('antrian/kiosk-pin', KioskPinGate::class)->name('queue.kiosk.pin');

Route::get('antrian/ambil', KioskTake::class)
    ->middleware('kiosk.unlocked')
    ->name('queue.kiosk.take');

// Read-only waiting-room TV display — no PIN needed, it can't write anything.
Route::get('antrian/layar', Display::class)->name('queue.display');
