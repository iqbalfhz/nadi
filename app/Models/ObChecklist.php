<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\ObChecklistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $ob_area_id
 * @property int $user_id
 * @property string|null $notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ob_area_id', 'user_id', 'notes', 'submitted_at'])]
class ObChecklist extends Model implements HasMedia
{
    /** @use HasFactory<ObChecklistFactory> */
    use HasFactory, InteractsWithMedia, LogsNadiActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public static function activitySubjectLabel(): string
    {
        return 'Checklist OB';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            // 'internal', not 'public': these are evidence photos, and the
            // public disk publishes them at a guessable /storage/{id}/ URL
            // with no login. See config/filesystems.php.
            ->useDisk('internal')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    /**
     * @return BelongsTo<ObArea, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(ObArea::class, 'ob_area_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
