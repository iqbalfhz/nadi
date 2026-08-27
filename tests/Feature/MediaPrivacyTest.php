<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Evidence photos — OB cleaning checklists, security patrol and incident
 * photos, courier proof-of-delivery — used to live on the 'public' disk,
 * which publishes them at /storage/{media_id}/{file} with no login and
 * sequential ids, so finding one meant being able to walk the whole archive.
 */
class MediaPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $directoriesToClean = [];

    protected function tearDown(): void
    {
        // The signature tests below deliberately use the real disk, so their
        // files have to be swept up by hand.
        foreach ($this->directoriesToClean as $directory) {
            Storage::disk('internal')->deleteDirectory($directory);
        }

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function evidenceCollections(): array
    {
        return [
            'OB checklist photos' => [ObChecklist::class, 'photos'],
            'security patrol photos' => [SecurityPatrol::class, 'photos'],
            'courier proof of delivery' => [MessengerDelivery::class, 'proof'],
        ];
    }

    #[DataProvider('evidenceCollections')]
    public function test_evidence_photos_are_stored_off_the_public_disk(string $model, string $collection): void
    {
        Storage::fake('public');
        Storage::fake('internal');

        $record = $model::factory()->create();
        $record->addMedia(UploadedFile::fake()->image('bukti.jpg'))->toMediaCollection($collection);

        $this->assertSame('internal', $record->fresh()->getFirstMedia($collection)?->disk);
        Storage::disk('public')->assertDirectoryEmpty('');
    }

    public function test_a_private_photo_cannot_be_opened_without_a_signature(): void
    {
        $media = $this->uploadForReal();

        // Walking media ids is exactly what the old public disk allowed.
        $this->get("/internal-media/{$media->id}/{$media->file_name}")
            ->assertForbidden();
    }

    public function test_a_signed_url_does_open_the_photo(): void
    {
        $media = $this->uploadForReal();

        $this->get($media->getTemporaryUrl(now()->addMinutes(5)))->assertOk();
    }

    public function test_an_expired_signature_stops_working(): void
    {
        $media = $this->uploadForReal();

        $url = $media->getTemporaryUrl(now()->addMinutes(5));

        $this->travel(10)->minutes();

        $this->get($url)->assertForbidden();
    }

    public function test_a_tampered_signature_stops_working(): void
    {
        $media = $this->uploadForReal();

        $url = $media->getTemporaryUrl(now()->addMinutes(5));

        $this->get($url.'0')->assertForbidden();
    }

    public function test_the_migration_moves_photos_that_were_already_public(): void
    {
        Storage::fake('public');
        Storage::fake('internal');

        $checklist = ObChecklist::factory()->create();
        $checklist->addMedia(UploadedFile::fake()->image('lama.jpg'))->toMediaCollection('photos');

        $media = $checklist->fresh()->getFirstMedia('photos');

        // Put it back exactly the way it was stored before this fix.
        Storage::disk('public')->put("{$media->id}/{$media->file_name}", 'isi-lama');
        Storage::disk('internal')->deleteDirectory((string) $media->id);
        DB::table('media')->where('id', $media->id)->update(['disk' => 'public', 'conversions_disk' => 'public']);

        $this->runMediaMigration();

        Storage::disk('public')->assertMissing("{$media->id}/{$media->file_name}");
        Storage::disk('internal')->assertExists("{$media->id}/{$media->file_name}");
        $this->assertSame('internal', DB::table('media')->where('id', $media->id)->value('disk'));
        $this->assertSame('internal', DB::table('media')->where('id', $media->id)->value('conversions_disk'));
    }

    /**
     * Adverts play on the queue display screen, which has no login at all —
     * locking those away would blank the screen in the lobby.
     */
    public function test_adverts_stay_public_because_the_display_screen_has_no_login(): void
    {
        Storage::fake('public');

        $advertisement = Advertisement::factory()->create();
        $advertisement->addMedia(UploadedFile::fake()->image('iklan.jpg'))->toMediaCollection('file');

        $this->assertSame('public', $advertisement->fresh()->getFirstMedia('file')?->disk);
    }

    /**
     * Storage::fake() replaces temporaryUrl() with a dummy "?expiration="
     * link carrying no signature at all, so a faked disk would happily pass
     * tests against behaviour production never performs. These have to run
     * against the real disk.
     */
    private function uploadForReal(): Media
    {
        $checklist = ObChecklist::factory()->create();
        $checklist->addMedia(UploadedFile::fake()->image('bukti.jpg'))->toMediaCollection('photos');

        $media = $checklist->fresh()->getFirstMedia('photos');

        $this->directoriesToClean[] = (string) $media->id;

        return $media;
    }

    private function runMediaMigration(): void
    {
        (require database_path('migrations/2026_08_27_120000_move_internal_media_off_the_public_disk.php'))->up();
    }
}
