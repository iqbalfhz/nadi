<?php

namespace Tests\Feature\Api;

use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What a scanner actually reads off a patrol sticker.
 *
 * The first field test on a real handset failed outright here: the QR holds a
 * URL, the docs implied a bare code, and a URL pushed into a path segment does
 * not even reach the controller — the guard saw a frozen card and no message.
 *
 * These pin both halves: the sticker's format, and the server's willingness to
 * take whatever came off it.
 */
class ScannedCodeTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = 'View:SecurityScan';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
    }

    /**
     * The sticker holds a URL so a guard without the app can point a stock
     * camera at it and land on the web form. Changing that shape would break
     * every handset in the field at once, which is why it is pinned here and
     * not merely described in a document.
     */
    public function test_the_sticker_holds_the_scan_url_and_its_last_segment_is_the_code(): void
    {
        $checkpoint = SecurityCheckpoint::factory()->create();

        $this->assertSame(
            url("/app/security-scan/{$checkpoint->code}"),
            $checkpoint->scan_url,
        );

        $this->assertSame(
            $checkpoint->code,
            SecurityCheckpoint::codeFromScan($checkpoint->scan_url),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function scanShapes(): array
    {
        return [
            'URL penuh' => ['https://nadi.example.com/app/security-scan/CODE'],
            'URL dengan slash penutup' => ['https://nadi.example.com/app/security-scan/CODE/'],
            'URL http biasa' => ['http://nadi.example.com/app/security-scan/CODE'],
            'URL dengan query' => ['https://nadi.example.com/app/security-scan/CODE?utm=qr'],
            'kode telanjang' => ['CODE'],
            'kode dengan spasi di ujung' => ['  CODE  '],
        ];
    }

    #[DataProvider('scanShapes')]
    public function test_the_scan_endpoint_takes_whatever_the_camera_read(string $shape): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $checkpoint = SecurityCheckpoint::factory()->create(['name' => 'Pos Parkir P2']);
        $scanned = str_replace('CODE', $checkpoint->code, $shape);

        $this->getJson('/api/v1/security/scan?scanned='.urlencode($scanned))
            ->assertOk()
            ->assertJsonPath('data.name', 'Pos Parkir P2');
    }

    /**
     * The half that matters most offline: a patrol queued in a basement
     * carries the raw scan, and would otherwise fail validation hours later —
     * long after the guard left the post and could do anything about it.
     */
    public function test_a_patrol_can_be_filed_with_the_raw_scan(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $checkpoint = SecurityCheckpoint::factory()->create();

        $this->postJson('/api/v1/security/patrols', [
            'checkpoint_code' => $checkpoint->scan_url,
            'photo_ids' => [$this->upload()],
        ], $this->idempotencyHeader())->assertCreated();

        $this->assertSame(
            $checkpoint->id,
            SecurityPatrol::query()->sole()->security_checkpoint_id,
        );
    }

    public function test_an_empty_scan_says_so_rather_than_hunting_for_a_checkpoint(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $this->getJson('/api/v1/security/scan?scanned=')->assertStatus(400);
        $this->getJson('/api/v1/security/scan')->assertStatus(400);
    }

    /**
     * A QR from another system resolves to nothing, and must say the thing
     * that helps: scan again.
     */
    public function test_a_foreign_qr_is_refused_as_unrecognised(): void
    {
        $this->actingAsMobileUser(self::PERMISSION);

        $this->getJson('/api/v1/security/scan?scanned='.urlencode('https://wa.me/628123456789'))
            ->assertNotFound();
    }

    private function upload(): string
    {
        return $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader())->json('data.id');
    }
}
