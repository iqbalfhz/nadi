<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Laravel 11 stopped putting this on the base controller, and until the
     * mobile API arrived nothing here needed it — the panels authorize
     * through Filament and Shield instead.
     *
     * API controllers have no such layer above them: $this->authorize() is
     * the only thing standing between a token and someone else's record, so
     * it belongs where every one of them can reach it.
     */
    use AuthorizesRequests;
}
