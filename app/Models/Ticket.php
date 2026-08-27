<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use App\Enums\TicketPaymentMethod;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @property TicketPaymentMethod $payment_method
 */
#[Fillable(['event_id', 'buyer_name', 'is_member', 'member_reference', 'payment_method', 'price', 'sold_by'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, LogsNadiActivity;

    /**
     * Written many times a day, and each row already records who created it
     * and when — logging creation again would bury the edits worth reading.
     *
     * @var array<int, string>
     */
    protected static array $recordEvents = ['updated', 'deleted'];

    public static function activitySubjectLabel(): string
    {
        return 'Tiket Event';
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            // Random, not sequential — printed as a receipt reference only,
            // not a serialized ticket number (see sellFor()'s own docblock:
            // this app deliberately has no locking/counter for numbering
            // tickets, only for the is_open check).
            do {
                $number = 'TRX'.now()->format('ymd').strtoupper(Str::random(6));
            } while (static::query()->where('transaction_number', $number)->exists());

            $ticket->transaction_number = $number;
        });
    }

    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'payment_method' => TicketPaymentMethod::class,
            'price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function soldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * Sells the next ticket for an event — locks the event row and re-checks
     * is_open inside the transaction (not just in the UI) so a box office
     * being closed mid-sale can't let a sale through anyway.
     */
    public static function sellFor(
        Event $event,
        User $cashier,
        string $buyerName,
        bool $isMember,
        ?string $memberReference,
        TicketPaymentMethod $paymentMethod,
    ): self {
        return DB::transaction(function () use ($event, $cashier, $buyerName, $isMember, $memberReference, $paymentMethod): self {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_open) {
                throw new RuntimeException('Loket untuk event ini sudah ditutup.');
            }

            return static::create([
                'event_id' => $locked->id,
                'buyer_name' => $buyerName,
                'is_member' => $isMember,
                'member_reference' => $memberReference,
                'payment_method' => $paymentMethod,
                'price' => $locked->priceFor($isMember),
                'sold_by' => $cashier->id,
            ]);
        });
    }
}
