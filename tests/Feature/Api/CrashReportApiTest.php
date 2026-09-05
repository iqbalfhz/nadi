<?php

namespace Tests\Feature\Api;

use App\Models\AppCrashReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The only route a failure in the field has to anyone.
 *
 * The APK is handed to officers directly, so there is no store-provided crash
 * reporting. Before this endpoint, a crash on a guard's phone at 3am was known
 * only to that phone.
 */
class CrashReportApiTest extends TestCase
{
    use RefreshDatabase;

    private const CRASH = [
        'message' => 'Null check operator used on a null value',
        'stack' => "#0 _PatrolFormScreenState._pindai\n#1 _InkResponseState.handleTap\n#2 GestureRecognizer.invokeCallback",
        'app_version' => '1.0.3+4',
        'platform' => 'android',
        'device' => 'Xiaomi 24115RA8EG',
        'os_version' => '16',
    ];

    public function test_the_app_can_report_its_own_failure(): void
    {
        $user = $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', [
            ...self::CRASH,
            'occurred_at' => '2026-09-05T07:23:57Z',
        ])->assertCreated();

        $report = AppCrashReport::query()->sole();

        $this->assertSame(self::CRASH['message'], $report->message);
        $this->assertSame('1.0.3+4', $report->app_version);
        $this->assertSame('Xiaomi 24115RA8EG', $report->device);
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame(1, $report->occurrences);

        // Jakarta wall-clock, same rule as a field report's submitted_at.
        $this->assertSame('2026-09-05 14:23', $report->first_occurred_at->format('Y-m-d H:i'));
    }

    /**
     * A screen that fails on every rebuild can send dozens of identical
     * reports in seconds. Two hundred copies of one bug hide the other three.
     */
    public function test_the_same_failure_is_counted_not_repeated(): void
    {
        $this->actingAsMobileUser([]);

        foreach (range(1, 4) as $ignored) {
            $this->postJson('/api/v1/crash', self::CRASH)->assertCreated();
        }

        $this->assertSame(1, AppCrashReport::query()->count());
        $this->assertSame(4, AppCrashReport::query()->sole()->occurrences);
    }

    /**
     * The single most useful thing this table can say is "the bug you fixed is
     * back". Merging a new version into the old row would hide exactly that,
     * so the version is part of what makes a crash distinct.
     */
    public function test_the_same_failure_in_a_new_version_is_a_new_row(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', [...self::CRASH, 'app_version' => '1.0.3+4'])->assertCreated();
        $this->postJson('/api/v1/crash', [...self::CRASH, 'app_version' => '1.0.4+5'])->assertCreated();

        $this->assertSame(2, AppCrashReport::query()->count());
    }

    /**
     * Two genuinely different bugs must not collapse into one row just because
     * Flutter words half its failures identically.
     */
    public function test_a_different_stack_is_a_different_crash(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', self::CRASH)->assertCreated();
        $this->postJson('/api/v1/crash', [
            ...self::CRASH,
            'stack' => "#0 _ObFormScreenState._kirim\n#1 _InkResponseState.handleTap",
        ])->assertCreated();

        $this->assertSame(2, AppCrashReport::query()->count());
    }

    /**
     * Memory addresses differ on every single run. Without normalising them,
     * grouping would never group anything and the table would be the flat list
     * it exists to avoid.
     */
    public function test_differing_memory_addresses_do_not_split_one_crash(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', [...self::CRASH, 'stack' => '#0 foo (package:nadi/a.dart:1) 0x7f2a11'])->assertCreated();
        $this->postJson('/api/v1/crash', [...self::CRASH, 'stack' => '#0 foo (package:nadi/a.dart:1) 0x9c4b88'])->assertCreated();

        $this->assertSame(1, AppCrashReport::query()->count());
        $this->assertSame(2, AppCrashReport::query()->sole()->occurrences);
    }

    /**
     * A crash is the app telling on itself while something is already wrong.
     * Everything but the message is optional, because refusing the report over
     * a missing device name throws away the only evidence there is.
     */
    public function test_a_bare_message_is_enough(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', ['message' => 'Terjadi galat tak terduga'])->assertCreated();

        $this->assertSame(1, AppCrashReport::query()->count());
    }

    public function test_a_report_without_a_message_is_refused(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', ['stack' => '#0 foo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    /**
     * Unlike every other write, this one carries no Idempotency-Key: a repeat
     * here is the information, not a mistake to be swallowed. Grouping in the
     * model does the deduplication instead, and does it across devices.
     */
    public function test_no_idempotency_key_is_required(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', self::CRASH)->assertCreated();
    }

    /**
     * Reachable without module access on purpose. An account that just lost
     * its permissions is a plausible cause of the crash being reported, and a
     * crash reporter that goes quiet exactly when something breaks is worth
     * nothing.
     */
    public function test_an_account_without_module_access_can_still_report(): void
    {
        // No permissions at all — this user fails EnsureMobileAccess.
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', self::CRASH)->assertCreated();

        // The same token, on a route that is behind that gate.
        $this->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_an_unauthenticated_request_is_refused(): void
    {
        $this->postJson('/api/v1/crash', self::CRASH)->assertUnauthorized();

        $this->assertSame(0, AppCrashReport::query()->count());
    }

    /**
     * The trace is only useful with somebody to ask about it, but a departed
     * employee must not take the bug report with them.
     */
    public function test_a_deleted_reporter_does_not_delete_the_crash(): void
    {
        $user = $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', self::CRASH)->assertCreated();

        $user->delete();

        $report = AppCrashReport::query()->sole();

        $this->assertNull($report->fresh()->user_id);
        $this->assertSame(self::CRASH['message'], $report->message);
    }

    /**
     * A stack that arrives megabytes long is a client bug of its own, and must
     * not be able to write them straight into the table.
     */
    public function test_an_oversized_stack_is_refused(): void
    {
        $this->actingAsMobileUser([]);

        $this->postJson('/api/v1/crash', [
            ...self::CRASH,
            'stack' => str_repeat('#0 frame ', 5000),
        ])->assertUnprocessable()->assertJsonValidationErrors('stack');
    }

    /**
     * A crash queued on a handset for two days can arrive after one that
     * happened this morning, so the window has to widen at both ends rather
     * than simply track the newest arrival.
     */
    public function test_a_late_arrival_widens_the_window_rather_than_overwriting_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/v1/crash', [...self::CRASH, 'occurred_at' => '2026-09-05T10:00:00+07:00'])->assertCreated();
        $this->postJson('/api/v1/crash', [...self::CRASH, 'occurred_at' => '2026-09-03T08:00:00+07:00'])->assertCreated();

        $report = AppCrashReport::query()->sole();

        $this->assertSame('2026-09-03 08:00', $report->first_occurred_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-05 10:00', $report->last_occurred_at->format('Y-m-d H:i'));
    }
}
