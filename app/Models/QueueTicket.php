<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use App\Enums\QueueTicketStatus;
use Database\Factories\QueueTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property QueueTicketStatus $status
 * @property Carbon|null $called_at
 * @property Carbon|null $done_at
 */
#[Fillable(['queue_category_id', 'number', 'status', 'counter_label', 'called_by', 'called_at', 'done_at'])]
class QueueTicket extends Model
{
    /** @use HasFactory<QueueTicketFactory> */
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
        return 'Nomor Antrian';
    }

    protected function casts(): array
    {
        return [
            'status' => QueueTicketStatus::class,
            'called_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QueueCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QueueCategory::class, 'queue_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function calledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->category->code.str_pad((string) $this->number, 3, '0', STR_PAD_LEFT),
        );
    }

    /**
     * Issue the next ticket for a category, scoped to today and safe under
     * concurrent kiosk taps — locks the category row so two visitors tapping
     * at the same instant can't be handed the same number.
     */
    public static function createNextFor(QueueCategory $category): self
    {
        return DB::transaction(function () use ($category): self {
            QueueCategory::query()->whereKey($category->id)->lockForUpdate()->first();

            $lastNumber = static::query()
                ->where('queue_category_id', $category->id)
                ->whereDate('created_at', today())
                ->max('number');

            return static::create([
                'queue_category_id' => $category->id,
                'number' => ((int) $lastNumber) + 1,
            ]);
        });
    }

    /**
     * Claim the oldest waiting ticket for a category — locks the row so two
     * operators clicking "call next" at the same time can't claim the same ticket.
     */
    public static function callNextFor(QueueCategory $category, User $operator, string $counterLabel): ?self
    {
        return DB::transaction(function () use ($category, $operator, $counterLabel): ?self {
            $ticket = static::query()
                ->where('queue_category_id', $category->id)
                ->where('status', QueueTicketStatus::Waiting)
                ->orderBy('number')
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                return null;
            }

            $ticket->update([
                'status' => QueueTicketStatus::Called,
                'counter_label' => $counterLabel,
                'called_by' => $operator->id,
                'called_at' => Carbon::now(),
            ]);

            return $ticket;
        });
    }
}
