<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Deactivating a user should take effect immediately, not just block
     * future logins — drop what is already signed in so an open browser tab
     * is kicked out on its next request, and a phone with it.
     *
     * Both halves matter. Sessions alone would leave the mobile app running
     * on a bearer token indefinitely, which is the wrong way round: the
     * phone is the device that walks out of the building.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('is_active') && ! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->tokens()->delete();
        }
    }
}
