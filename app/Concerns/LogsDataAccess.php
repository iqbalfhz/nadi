<?php

namespace App\Concerns;

/**
 * Records *reading* sensitive data, not just changing it.
 *
 * Every other part of Riwayat Aktivitas answers "who changed this". An audit
 * usually also has to answer "who took a copy of it" — who exported the
 * sales figures, who opened the patrol photos. Those leave no trace at all
 * otherwise: nothing is written, so no model event fires.
 */
trait LogsDataAccess
{
    protected function logDataAccess(string $description, string $what): void
    {
        activity('akses-data')
            ->withProperty('data', $what)
            ->log($description);
    }
}
