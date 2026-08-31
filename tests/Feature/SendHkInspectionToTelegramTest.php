<?php

namespace Tests\Feature;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Filament\Pages\ManageTelegramSettings;
use App\Jobs\SendHkInspectionToTelegram;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use App\Models\User;
use App\Settings\TelegramSettings;
use App\Support\HkInspectionMessage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SendHkInspectionToTelegramTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456789:AAFtest';

    private const CHAT_ID = '-1001234567890';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTelegram(enabled: true);
    }

    private function configureTelegram(bool $enabled): void
    {
        $settings = app(TelegramSettings::class);
        $settings->enabled = $enabled;
        $settings->bot_token = $enabled ? self::TOKEN : '';
        $settings->chat_id = $enabled ? self::CHAT_ID : '';
        $settings->save();

        // The job resolves the settings out of the container, which caches the
        // instance built before this override.
        $this->app->forgetInstance(TelegramSettings::class);
    }

    private function inspection(HkCondition $condition = HkCondition::Bersih): HkInspection
    {
        $category = HkCategory::factory()->create(['name' => 'Toilet']);
        $area = HkArea::factory()->for($category, 'category')->create(['name' => 'Lt 2 Zona A']);
        $supervisor = User::factory()->create(['name' => 'Andi Pratama']);

        $factory = HkInspection::factory()
            ->for($area, 'area')
            ->for($supervisor, 'user');

        if ($condition->needsFollowUp()) {
            $factory = $factory->withFinding($condition);
        }

        return $factory->create([
            'hk_category_id' => $category->id,
            'staff_name' => 'Budi',
            'shift' => HkShift::Pagi,
        ]);
    }

    public function test_a_report_without_photos_is_sent_as_a_message(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $inspection = $this->inspection();

        (new SendHkInspectionToTelegram($inspection->id))->handle(app(TelegramSettings::class));

        Http::assertSent(function (Request $request) use ($inspection): bool {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === self::CHAT_ID
                && str_contains((string) $request['text'], $inspection->area->name);
        });
    }

    public function test_a_single_photo_is_uploaded_with_the_report_as_its_caption(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $inspection = $this->inspection();
        $inspection->addMedia(UploadedFile::fake()->image('toilet.jpg'))->toMediaCollection('photos');

        (new SendHkInspectionToTelegram($inspection->id))->handle(app(TelegramSettings::class));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/sendPhoto'));

        $inspection->clearMediaCollection('photos');
    }

    public function test_several_photos_are_sent_as_one_album(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $inspection = $this->inspection();
        $inspection->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('photos');
        $inspection->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('photos');

        (new SendHkInspectionToTelegram($inspection->refresh()->id))->handle(app(TelegramSettings::class));

        // One album, not one message per photo — a round of nine toilets would
        // otherwise bury the group.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/sendMediaGroup'));

        $inspection->clearMediaCollection('photos');
    }

    public function test_nothing_is_sent_while_the_integration_is_switched_off(): void
    {
        Http::fake();
        $this->configureTelegram(enabled: false);

        $inspection = $this->inspection();

        (new SendHkInspectionToTelegram($inspection->id))->handle(app(TelegramSettings::class));

        Http::assertNothingSent();
    }

    /**
     * The report is already saved by the time this job runs. Telegram being
     * unreachable must cost a notification, never the record.
     */
    public function test_a_rejected_send_leaves_the_report_untouched(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);

        $inspection = $this->inspection();

        try {
            (new SendHkInspectionToTelegram($inspection->id))->handle(app(TelegramSettings::class));
            $this->fail('Expected the job to fail so the queue retries it.');
        } catch (RuntimeException) {
            // Expected: the job throws so the worker retries it later.
        }

        $this->assertModelExists($inspection);
        $this->assertSame(1, HkInspection::query()->count());
    }

    public function test_a_report_deleted_before_delivery_is_skipped_quietly(): void
    {
        Http::fake();

        $inspection = $this->inspection();
        $id = $inspection->id;
        $inspection->delete();

        (new SendHkInspectionToTelegram($id))->handle(app(TelegramSettings::class));

        Http::assertNothingSent();
    }

    public function test_an_admin_can_open_the_telegram_settings_page(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        Livewire::test(ManageTelegramSettings::class)
            ->assertOk()
            ->assertSee('Kirim ke Telegram');
    }

    /**
     * Pressing "Kirim Tes" before anything is configured must explain what is
     * missing, not fire a doomed request at Telegram and report its refusal.
     */
    public function test_the_test_button_explains_itself_when_nothing_is_configured(): void
    {
        Http::fake();
        $this->configureTelegram(enabled: false);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        Livewire::test(ManageTelegramSettings::class)->callAction('test');

        Http::assertNothingSent();
    }

    public function test_the_message_reads_as_a_report_not_as_data(): void
    {
        $message = HkInspectionMessage::for($this->inspection(HkCondition::Kotor));

        $this->assertStringContainsString('LAPORAN HOUSEKEEPING', $message);
        $this->assertStringContainsString('Toilet', $message);
        $this->assertStringContainsString('Lt 2 Zona A', $message);
        $this->assertStringContainsString('Kotor', $message);
        $this->assertStringContainsString('Pagi', $message);
        $this->assertStringContainsString('Budi', $message);
        $this->assertStringContainsString('Andi Pratama', $message);
        $this->assertStringContainsString('Tindak Lanjut:', $message);
    }

    /**
     * "Lantai" only exists for categories that ask for it, so the row is
     * absent rather than printed blank everywhere else.
     */
    public function test_the_floor_row_is_omitted_when_there_is_no_floor(): void
    {
        $withoutFloor = HkInspectionMessage::for($this->inspection());

        $this->assertStringNotContainsString('Lantai', $withoutFloor);
        $this->assertStringNotContainsString('Tindak Lanjut', $withoutFloor);

        $publicArea = HkCategory::factory()->requiringFloor()->create(['name' => 'Public Area']);
        $withFloor = HkInspection::factory()
            ->for(HkArea::factory()->for($publicArea, 'category'), 'area')
            ->create(['hk_category_id' => $publicArea->id, 'floor' => 'Lantai 3']);

        $this->assertStringContainsString('Lantai', HkInspectionMessage::for($withFloor));
        $this->assertStringContainsString('Lantai 3', HkInspectionMessage::for($withFloor));
    }
}
