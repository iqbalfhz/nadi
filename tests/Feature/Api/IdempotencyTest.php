<?php

namespace Tests\Feature\Api;

use App\Models\ObArea;
use App\Models\ObChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The contract that makes an offline outbox safe.
 *
 * A phone that loses signal mid-submission cannot tell "saved" from "never
 * arrived". Everything here is about making the retry it must then send
 * harmless.
 */
class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:ObChecklist', 'Create:ObChecklist'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
    }

    public function test_retrying_a_submission_does_not_file_it_twice(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $payload = $this->payload();
        $headers = $this->idempotencyHeader();

        $first = $this->postJson('/api/v1/ob/checklists', $payload, $headers);
        $second = $this->postJson('/api/v1/ob/checklists', $payload, $headers);

        $first->assertCreated();
        $second->assertCreated();

        // The point of the whole mechanism: one report, not two.
        $this->assertSame(1, ObChecklist::query()->count());

        // And the retry is answered with the original, not a fresh record.
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $second->assertHeader('Idempotency-Replayed', 'true');
    }

    public function test_a_key_reused_for_a_different_action_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $key = (string) Str::uuid();

        $this->postJson('/api/v1/ob/checklists', $this->payload(), $this->idempotencyHeader($key))
            ->assertCreated();

        // Answering this with the checklist's response would hide a real
        // client bug behind a success.
        $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader($key))->assertStatus(409);
    }

    public function test_a_write_without_a_key_is_refused(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $this->postJson('/api/v1/ob/checklists', $this->payload())
            ->assertStatus(400);

        $this->assertSame(0, ObChecklist::query()->count());
    }

    /**
     * A rejected payload must stay retryable under the same key. The outbox
     * holds one report and one key for it; forcing a new key after a
     * validation error would make the app invent one, which is where
     * duplicates come from.
     */
    public function test_a_rejected_submission_can_be_corrected_and_resent(): void
    {
        $this->actingAsMobileUser(self::PERMISSIONS);

        $headers = $this->idempotencyHeader();

        $this->postJson('/api/v1/ob/checklists', [
            'ob_area_id' => ObArea::factory()->create(['is_active' => true])->id,
            'photo_ids' => [],
        ], $headers)->assertStatus(422);

        $this->postJson('/api/v1/ob/checklists', $this->payload(), $headers)
            ->assertCreated();

        $this->assertSame(1, ObChecklist::query()->count());
    }

    /**
     * One key belongs to one account. Without the user scope, a leaked key
     * would let one worker read back another's response.
     */
    public function test_a_key_is_scoped_to_the_account_that_used_it(): void
    {
        $key = (string) Str::uuid();

        $this->actingAsMobileUser(self::PERMISSIONS);
        $this->postJson('/api/v1/ob/checklists', $this->payload(), $this->idempotencyHeader($key))
            ->assertCreated();

        $this->actingAsMobileUser(self::PERMISSIONS);
        $second = $this->postJson('/api/v1/ob/checklists', $this->payload(), $this->idempotencyHeader($key));

        $second->assertCreated();
        $second->assertHeaderMissing('Idempotency-Replayed');
        $this->assertSame(2, ObChecklist::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $upload = $this->postJson('/api/v1/uploads', [
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ], $this->idempotencyHeader());

        return [
            'ob_area_id' => ObArea::factory()->create(['is_active' => true])->id,
            'notes' => 'Lantai basah, sudah dipel ulang.',
            'photo_ids' => [$upload->json('data.id')],
        ];
    }
}
