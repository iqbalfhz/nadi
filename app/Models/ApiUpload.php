<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

/**
 * A photo the phone has sent but not yet attached to any report.
 *
 * See the migration for why uploads are a step of their own. Nothing here is
 * evidence yet — it becomes evidence when a report claims it, at which point
 * the file moves into that model's media collection and the row goes away.
 */
#[Fillable(['user_id', 'path', 'mime_type', 'size'])]
class ApiUpload extends Model
{
    use HasUuids;

    /**
     * The private disk every staged photo lands on. Never 'public': a photo
     * is evidence from the moment it is taken, not from the moment a report
     * claims it.
     */
    public const DISK = 'internal';

    /**
     * How long a staged photo waits for its report before the nightly
     * cleanup removes it. Long enough for a phone left in a locker over a
     * weekend.
     */
    public const RETENTION_DAYS = 7;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Remove the staged file along with the row. Used both when a report
     * claims the photo and when the nightly cleanup gives up on it, so a
     * deleted row never leaves the file behind.
     */
    public function discard(): void
    {
        Storage::disk(self::DISK)->delete($this->path);

        $this->delete();
    }

    /**
     * Move staged photos into a report's media collection.
     *
     * Ownership is re-checked here even though the form request already
     * validated it: this is the step that actually reads someone's file, and
     * a check at the point of use survives a future caller that forgets the
     * rule.
     *
     * @param  array<int, string>  $photoIds
     */
    public static function claim(HasMedia $record, array $photoIds, int $userId, string $collection = 'photos'): void
    {
        $uploads = self::query()
            ->whereIn('id', $photoIds)
            ->where('user_id', $userId)
            ->get();

        foreach ($uploads as $upload) {
            // addMedia() rather than addMediaFromDisk(): the latter lives on
            // the InteractsWithMedia trait, so a HasMedia parameter cannot
            // promise it. Storage::path() is safe because 'internal' is a
            // local disk by design — and if that ever changes, this throws
            // rather than quietly filing photos in the wrong place.
            $record
                ->addMedia(Storage::disk(self::DISK)->path($upload->path))
                ->toMediaCollection($collection);

            $upload->discard();
        }
    }
}
