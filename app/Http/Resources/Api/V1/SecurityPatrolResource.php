<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SecurityPatrol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SecurityPatrol
 */
class SecurityPatrolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checkpoint' => new SecurityCheckpointResource($this->whenLoaded('checkpoint')),
            'incident_report' => $this->incident_report,
            'photo_count' => $this->getMedia('photos')->count(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
