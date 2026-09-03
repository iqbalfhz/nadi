<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ObChecklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ObChecklist
 */
class ObChecklistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area' => new ObAreaResource($this->whenLoaded('area')),
            'notes' => $this->notes,

            // Photos are not inlined: every URL is signed and expires, so
            // handing them out with a list of 20 reports would mint 60 links
            // nobody asked for and log an access that never happened. The
            // count tells the app whether to offer the photos endpoint.
            'photo_count' => $this->getMedia('photos')->count(),

            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
