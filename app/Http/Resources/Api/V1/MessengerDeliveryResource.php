<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MessengerDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MessengerDelivery
 */
class MessengerDeliveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            // Where to collect. Without it a courier knows where the
            // document is going and not where to fetch it — which is how
            // the first field test ended.
            'origin' => $this->origin,

            'destination' => $this->destination,

            // Who to ask when the document is not where it should be.
            'requester' => $this->whenLoaded('sender', fn (): array => [
                'name' => $this->sender->name,
                'department' => $this->sender->department?->name,
            ]),
            'document_description' => $this->document_description,

            // Both the machine value and the label: the app branches on the
            // value and shows the label, so neither has to be hardcoded on
            // the handset.
            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            'claimed_at' => $this->claimed_at?->toIso8601String(),
            'in_transit_at' => $this->in_transit_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
