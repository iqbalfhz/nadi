<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a given Idempotency-Key already produced.
 *
 * See the migration for why this exists. Written by
 * App\Http\Middleware\EnsureIdempotency and read by nothing else.
 */
#[Fillable(['user_id', 'key', 'endpoint', 'status', 'response'])]
class ApiIdempotencyKey extends Model
{
    /**
     * How long a key is remembered. A phone that comes back online after this
     * window will create a duplicate rather than replay — which is the right
     * trade at a week's distance, since by then nobody can tell the two
     * submissions apart anyway.
     */
    public const RETENTION_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'array',
            'status' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
