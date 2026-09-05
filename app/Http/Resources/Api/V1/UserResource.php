<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Settings\MobileAppSettings;
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

            // Which build the handset should be running. Carried here rather
            // than on an endpoint of its own because the app already calls
            // /me on every launch — no extra request, and nothing new for the
            // app to remember to do.
            //
            // Omitted entirely while nothing is configured, and omitted is a
            // shape the field builds already handle: no banner, no block. So
            // this can be switched on any day without breaking a handset
            // nobody has updated.
            'app' => $this->whenNotNull(app(MobileAppSettings::class)->forApi()),
        ];
    }
}
