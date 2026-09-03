<?php

namespace App\Http\Resources\Api\V1;

use App\Models\HkCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HkCategory
 */
class HkCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Drives whether the app shows the Lantai field. Sent with the
            // category so the phone can render the conditional form offline.
            'requires_floor' => $this->requires_floor,

            'areas' => HkAreaResource::collection($this->whenLoaded('areas')),
        ];
    }
}
