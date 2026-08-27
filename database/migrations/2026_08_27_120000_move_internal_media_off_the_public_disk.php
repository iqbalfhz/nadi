<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves already-uploaded evidence photos — OB checklists, security patrols,
 * courier proof-of-delivery — off the public disk, where they were reachable
 * at /storage/{media_id}/... by anyone, with no login and sequential IDs to
 * walk. New uploads already land on 'internal' (see each model's
 * registerMediaCollections()); this catches everything stored before that.
 *
 * Done as a migration rather than a console command so it runs itself exactly
 * once on deploy, and is recorded as having run.
 */
return new class extends Migration
{
    /**
     * Media Library's DefaultPathGenerator stores every file for one media
     * row under a directory named after its id, conversions and responsive
     * images included — so moving that whole directory moves everything.
     *
     * @var array<string, string>
     */
    private const COLLECTIONS = [
        'App\Models\ObChecklist' => 'photos',
        'App\Models\SecurityPatrol' => 'photos',
        'App\Models\MessengerDelivery' => 'proof',
    ];

    public function up(): void
    {
        $this->moveBetweenDisks('public', 'internal');
    }

    public function down(): void
    {
        $this->moveBetweenDisks('internal', 'public');
    }

    private function moveBetweenDisks(string $from, string $to): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);

        foreach (self::COLLECTIONS as $modelType => $collection) {
            $rows = DB::table('media')
                ->where('model_type', $modelType)
                ->where('collection_name', $collection)
                ->where('disk', $from)
                ->get(['id']);

            foreach ($rows as $row) {
                $directory = (string) $row->id;

                foreach ($source->allFiles($directory) as $file) {
                    // Streamed rather than read into memory: patrol photos
                    // run up to 10MB each and a long-running site can have
                    // thousands of them.
                    $target->writeStream($file, $source->readStream($file));
                }

                $source->deleteDirectory($directory);

                DB::table('media')
                    ->where('id', $row->id)
                    ->update(['disk' => $to, 'conversions_disk' => $to]);
            }
        }
    }
};
