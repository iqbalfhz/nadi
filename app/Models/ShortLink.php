<?php

namespace App\Models;

use Database\Factories\ShortLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['target_url', 'created_by'])]
class ShortLink extends Model
{
    /** @use HasFactory<ShortLinkFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $shortLink): void {
            // `code` isn't fillable, so a new instance never has one set —
            // no need to guard against overwriting an existing value here.
            do {
                $code = Str::random(7);
            } while (static::query()->where('code', $code)->exists());

            $shortLink->code = $code;
        });
    }

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'last_clicked_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function shortUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => URL::to("/s/{$this->code}"),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
