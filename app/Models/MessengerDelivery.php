<?php

namespace App\Models;

use App\Enums\MessengerDeliveryStatus;
use Database\Factories\MessengerDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property MessengerDeliveryStatus $status
 * @property Carbon|null $claimed_at
 * @property Carbon|null $in_transit_at
 * @property Carbon|null $delivered_at
 */
#[Fillable(['tracking_number', 'sender_id', 'destination', 'document_description', 'status', 'messenger_id', 'claimed_at', 'in_transit_at', 'delivered_at'])]
class MessengerDelivery extends Model implements HasMedia
{
    /** @use HasFactory<MessengerDeliveryFactory> */
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        static::creating(function (self $delivery): void {
            $delivery->tracking_number ??= 'MSG-'.strtoupper(Str::random(8));
            $delivery->status ??= MessengerDeliveryStatus::Available;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => MessengerDeliveryStatus::class,
            'claimed_at' => 'datetime',
            'in_transit_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function messenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messenger_id');
    }

    public static function createFor(User $sender, string $destination, string $documentDescription): self
    {
        return static::create([
            'sender_id' => $sender->id,
            'destination' => $destination,
            'document_description' => $documentDescription,
        ]);
    }

    /**
     * Claims an open delivery for self-pickup. Locks the specific row so two
     * messengers tapping "Ambil" on the same task at the same instant can't
     * both succeed — the second transaction blocks until the first commits,
     * then sees the status has already changed and is rejected.
     */
    public static function claim(int $deliveryId, User $messenger): self
    {
        return DB::transaction(function () use ($deliveryId, $messenger): self {
            $delivery = static::query()->whereKey($deliveryId)->lockForUpdate()->firstOrFail();

            if ($delivery->status !== MessengerDeliveryStatus::Available) {
                throw new RuntimeException('Tugas ini sudah diambil messenger lain.');
            }

            $delivery->update([
                'status' => MessengerDeliveryStatus::PickedUp,
                'messenger_id' => $messenger->id,
                'claimed_at' => Carbon::now(),
            ]);

            return $delivery;
        });
    }

    public function markInTransit(User $messenger): void
    {
        if ($this->status !== MessengerDeliveryStatus::PickedUp || $this->messenger_id !== $messenger->id) {
            throw new RuntimeException('Tidak bisa mengubah status pengiriman ini.');
        }

        $this->update([
            'status' => MessengerDeliveryStatus::InTransit,
            'in_transit_at' => Carbon::now(),
        ]);
    }

    public function markDelivered(User $messenger): void
    {
        if ($this->status !== MessengerDeliveryStatus::InTransit || $this->messenger_id !== $messenger->id) {
            throw new RuntimeException('Tidak bisa mengubah status pengiriman ini.');
        }

        $this->update([
            'status' => MessengerDeliveryStatus::Delivered,
            'delivered_at' => Carbon::now(),
        ]);
    }
}
