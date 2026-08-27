<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\SecurityPatrolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['security_checkpoint_id', 'user_id', 'incident_report'])]
class SecurityPatrol extends Model implements HasMedia
{
    /** @use HasFactory<SecurityPatrolFactory> */
    use HasFactory, InteractsWithMedia, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Patroli Security';
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
     * @return BelongsTo<SecurityCheckpoint, $this>
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(SecurityCheckpoint::class, 'security_checkpoint_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
