<?php

namespace Tests\Feature;

use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Models\Advertisement;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertisementAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');
    }

    public function test_creating_an_advertisement_with_an_image_uploads_and_detects_type(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateAdvertisement::class)
            ->fillForm([
                'title' => 'Promo Agustus',
                'file' => UploadedFile::fake()->image('promo.jpg'),
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $advertisement = Advertisement::where('title', 'Promo Agustus')->firstOrFail();

        $this->assertFalse($advertisement->isVideo());
        $media = $advertisement->getFirstMedia('file');
        $this->assertNotNull($media);

        // The queue display screen is public with no login, so it can't use
        // Filament's default signed/private URLs — the file must land on the
        // public disk or the display page gets a dead link.
        $this->assertSame('public', $media->disk);
    }

    public function test_an_advertisement_is_treated_as_a_video_based_on_its_media_mime_type(): void
    {
        // Laravel's fake uploaded files have no real file content, so Media
        // Library's content-sniffed mime detection can't be driven through an
        // actual upload in a test — attach the media record directly instead,
        // to test isVideo()'s own logic rather than Symfony's mime sniffer.
        $advertisement = Advertisement::factory()->create();
        $advertisement->media()->create([
            'collection_name' => 'file',
            'name' => 'promo',
            'file_name' => 'promo.mp4',
            'mime_type' => 'video/mp4',
            'disk' => 'public',
            'size' => 1000,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $this->assertTrue($advertisement->refresh()->isVideo());
    }

    public function test_an_advertisement_with_no_media_is_not_treated_as_a_video(): void
    {
        $advertisement = Advertisement::factory()->create();

        $this->assertFalse($advertisement->isVideo());
    }
}
