<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Deactivating a user should take effect immediately, not just block
     * future logins — drop their existing sessions so an already-open
     * browser tab is kicked out on its next request.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('is_active') && ! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }
}
