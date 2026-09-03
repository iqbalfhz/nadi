<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use App\Enums\HkCondition;
use App\Enums\HkShift;
use Database\Factories\HkInspectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One supervisor's inspection of one housekeeping point.
 *
 * Unlike ObChecklist — where the person filing is the person who did the work
 * — this record has two people in it: `user_id` is the supervisor who
 * inspected, `staff_name` is the HK staff member being inspected. HK staff are
 * not NADI account holders, hence free text rather than a relation.
 *
 * @property HkCondition $condition
 * @property HkShift $shift
 */
/**
 * @property int $id
 * @property int $hk_category_id
 * @property int $hk_area_id
 * @property int $user_id
 * @property string $staff_name
 * @property HkShift $shift
 * @property HkCondition $condition
 * @property string|null $floor
 * @property string|null $notes
 * @property string|null $follow_up
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'hk_category_id',
    'hk_area_id',
    'user_id',
    'staff_name',
    'shift',
    'condition',
    'floor',
    'notes',
    'follow_up',
    'submitted_at',
])]
class HkInspection extends Model implements HasMedia
{
    /** @use HasFactory<HkInspectionFactory> */
    use HasFactory, InteractsWithMedia, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Checklist HK';
    }

    protected function casts(): array
    {
        return [
            'shift' => HkShift::class,
            'condition' => HkCondition::class,
            'submitted_at' => 'datetime',
        ];
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
     * @return BelongsTo<HkCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HkCategory::class, 'hk_category_id');
    }

    /**
     * @return BelongsTo<HkArea, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(HkArea::class, 'hk_area_id');
    }

    /**
     * The supervisor who filed this report.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
