<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'department' => $this->department?->name,

            // What the home screen should offer. Module keys rather than raw
            // Shield permission strings: the app has no business knowing that
            // 'Create:ObChecklist' is spelled that way, and this keeps the
            // naming free to change on the server.
            'modules' => $this->mobileModules(),
        ];
    }
}
