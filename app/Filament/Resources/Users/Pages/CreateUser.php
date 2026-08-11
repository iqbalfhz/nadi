<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = new User($data);

        // Admin is creating this account directly and vouching for the email
        // address, so there is no self-registration verification step to wait on.
        $user->email_verified_at = Carbon::now();
        $user->save();

        return $user;
    }
}
