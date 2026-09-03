<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SecurityCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SecurityCheckpoint
 */
class SecurityCheckpointResource extends JsonResource
{
    /**
     * The `code` is never returned.
     *
     * The caller already has it — that is how they asked — and echoing it back
     * would invite the app to store a growing list of codes, which is exactly
     * the thing that must not exist on a handset. See
     * SecurityPatrolController::resolve().
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
