<?php

namespace App\Http\Resources\Api\V1;

use App\Models\HkInspection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HkInspection
 */
class HkInspectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new HkCategoryResource($this->whenLoaded('category')),
            'area' => new HkAreaResource($this->whenLoaded('area')),
            'staff_name' => $this->staff_name,
            'shift' => $this->shift->value,
            'shift_label' => $this->shift->label(),
            'condition' => $this->condition->value,
            'condition_label' => $this->condition->label(),
            'floor' => $this->floor,
            'notes' => $this->notes,
            'follow_up' => $this->follow_up,
            'photo_count' => $this->getMedia('photos')->count(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
