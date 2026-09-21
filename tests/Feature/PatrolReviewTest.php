<?php

namespace Tests\Feature;

use App\Enums\PatrolReviewFlag;
use App\Filament\Resources\SecurityPatrols\Pages\ListSecurityPatrols;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use App\Support\PatrolReview;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Flagging patrols that deserve a second look — issue #1, Ask 1.
 *
 * Half of these tests are about what must *not* be flagged. Every signal here
 * has an honest explanation, so a rule that fires too eagerly does real harm:
 * it points a supervisor at the guard working in the basement, who is the one
 * with the worst signal and the best attendance.
 */
class PatrolReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $guard;

    private SecurityCheckpoint $lobby;

    private SecurityCheckpoint $basement;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');

        $this->guard = User::factory()->create();
        $this->lobby = SecurityCheckpoint::factory()->create(['name' => 'Pos Lobby']);
        $this->basement = SecurityCheckpoint::factory()->create(['name' => 'Pos Basement']);
    }

    /**
     * Nobody is at two posts ninety seconds apart, whatever the distance —
     * which is exactly why this rule needs no map. NADI has no coordinates
     * for its posts.
     */
    public function test_two_posts_at_once_is_flagged(): void
    {
        $first = $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $second = $this->patrol($this->basement, '2026-09-20 01:00:40');

        $this->assess();

        $this->assertSame([], $first->fresh()->review_flags ?? []);
        $this->assertContains(
            PatrolReviewFlag::ImpossibleTravel->value,
            $second->fresh()->review_flags ?? [],
        );
    }

    /**
     * A guard who genuinely walks between two posts must never be flagged.
     * This is the case the rule exists to leave alone.
     */
    public function test_a_normal_walk_between_posts_is_not_flagged(): void
    {
        $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $second = $this->patrol($this->basement, '2026-09-20 01:12:00');

        $this->assess();

        $this->assertNull($second->fresh()->review_flags);
    }

    public function test_the_same_post_scanned_twice_in_minutes_is_flagged(): void
    {
        $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $second = $this->patrol($this->lobby, '2026-09-20 01:04:00');

        $this->assess();

        $this->assertContains(
            PatrolReviewFlag::RepeatedCheckpoint->value,
            $second->fresh()->review_flags ?? [],
        );
    }

    /**
     * The same post on the next round is the job, not a repeat.
     */
    public function test_the_same_post_on_a_later_round_is_not_flagged(): void
    {
        $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $second = $this->patrol($this->lobby, '2026-09-20 02:00:00');

        $this->assess();

        $this->assertNull($second->fresh()->review_flags);
    }

    /**
     * Two guards are two people. A shift handover puts them at the same post
     * a minute apart, and that is not a finding about either of them.
     */
    public function test_two_guards_at_the_same_post_are_not_flagged(): void
    {
        $this->patrol($this->lobby, '2026-09-20 01:00:00');

        $other = SecurityPatrol::factory()->create([
            'user_id' => User::factory()->create()->id,
            'security_checkpoint_id' => $this->lobby->id,
            'submitted_at' => '2026-09-20 01:00:30',
            'created_at' => '2026-09-20 01:00:30',
        ]);

        $this->assess();

        $this->assertNull($other->fresh()->review_flags);
    }

    /**
     * The whole point of the offline design. Twelve reports written through
     * the night and flushed at 07:00 arrive in one burst — ordering them by
     * arrival would make every one of them look impossible.
     */
    public function test_an_outbox_arriving_all_at_once_is_not_flagged(): void
    {
        $walked = ['01:00:00', '01:40:00', '02:20:00', '03:00:00'];
        $posts = [$this->lobby, $this->basement, $this->lobby, $this->basement];

        foreach ($walked as $index => $time) {
            SecurityPatrol::factory()->create([
                'user_id' => $this->guard->id,
                'security_checkpoint_id' => $posts[$index]->id,
                'submitted_at' => "2026-09-20 {$time}",
                // All received within the same few seconds, in a jumbled order.
                'created_at' => '2026-09-20 07:00:0'.(9 - $index),
            ]);
        }

        $this->assess();

        $this->assertSame(
            0,
            SecurityPatrol::query()->whereNotNull('review_flags')->count(),
            'An outbox coming home must never look like a fabricated round.',
        );
    }

    /**
     * A time that has not happened yet has no honest explanation. The server
     * clamps it so the report survives; the claim is kept so it can be seen.
     */
    public function test_a_phone_clock_running_ahead_is_flagged(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'user_id' => $this->guard->id,
            'security_checkpoint_id' => $this->lobby->id,
            'submitted_at' => '2026-09-20 01:00:00',
            'submitted_at_claimed' => '2026-09-20 04:00:00',
            'created_at' => '2026-09-20 01:00:00',
        ]);

        $this->assess();

        $this->assertContains(
            PatrolReviewFlag::ClockAhead->value,
            $patrol->fresh()->review_flags ?? [],
        );
    }

    /**
     * A report filed hours before it arrived is the basement, not a lie. The
     * issue is explicit that this must not be flagged.
     */
    public function test_a_long_offline_delay_is_not_flagged(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'user_id' => $this->guard->id,
            'security_checkpoint_id' => $this->lobby->id,
            'submitted_at' => '2026-09-20 03:15:00',
            'created_at' => '2026-09-20 07:02:00',
        ]);

        $this->assess();

        $this->assertNull($patrol->fresh()->review_flags);
    }

    /**
     * Reports arrive late and out of order, so a scan's neighbours can turn
     * up after it did. Re-running has to settle on the same answer.
     */
    public function test_reassessing_is_stable_and_clears_stale_flags(): void
    {
        $first = $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $second = $this->patrol($this->basement, '2026-09-20 01:00:40');

        $this->assess();
        $this->assertNotNull($second->fresh()->review_flags);

        // A second pass changes nothing.
        $this->assertSame(0, $this->assess());

        // The earlier scan turns out to have been misfiled and is removed;
        // the flag it caused must go with it.
        $first->delete();
        $this->assess();

        $this->assertNull($second->fresh()->review_flags);
    }

    /**
     * Flagging must never look like somebody edited the report.
     */
    public function test_flagging_does_not_touch_the_report(): void
    {
        $patrol = $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $this->patrol($this->basement, '2026-09-20 01:00:20');

        $updatedAt = $patrol->updated_at;
        // Measured, not assumed zero: creating the fixtures above already
        // logged their own "created" entries.
        $logged = Activity::query()->count();

        $this->assess();

        $this->assertEquals($updatedAt, $patrol->fresh()->updated_at);
        $this->assertSame($logged, Activity::query()->count());
    }

    public function test_the_command_reports_what_it_found(): void
    {
        $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $this->patrol($this->basement, '2026-09-20 01:00:20');

        $this->travelTo('2026-09-20 08:00:00');

        $this->artisan('nadi:assess-patrols')
            ->expectsOutputToContain('Perlu ditinjau: 1')
            ->assertSuccessful();
    }

    /**
     * The working list a supervisor opens. It has to empty as they go, or it
     * becomes a wall nobody reads — so reviewed rows drop out of it.
     */
    public function test_the_review_filter_lists_only_what_is_still_waiting(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        // Pinned so the list's default "this month" date filter keeps
        // covering the fixed dates below after September is over.
        $this->travelTo('2026-09-21 12:00:00');

        $waiting = $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $waiting->forceFill(['review_flags' => [PatrolReviewFlag::ImpossibleTravel->value]])->save();

        $done = $this->patrol($this->basement, '2026-09-20 02:00:00');
        $done->forceFill([
            'review_flags' => [PatrolReviewFlag::ImpossibleTravel->value],
            'reviewed_at' => '2026-09-20 09:00:00',
        ])->save();

        $ordinary = $this->patrol($this->lobby, '2026-09-20 03:00:00');

        Livewire::test(ListSecurityPatrols::class)
            ->filterTable('needs_review')
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$done, $ordinary]);
    }

    /**
     * Marking a patrol reviewed only takes it off the list. The report itself
     * — what the guard said, where, and when — is not touched.
     */
    public function test_marking_reviewed_changes_nothing_but_the_review(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
        // Pinned so the list's default "this month" date filter keeps
        // covering the fixed dates below after September is over.
        $this->travelTo('2026-09-21 12:00:00');

        $patrol = $this->patrol($this->lobby, '2026-09-20 01:00:00');
        $patrol->forceFill(['review_flags' => [PatrolReviewFlag::ClockAhead->value]])->save();

        Livewire::test(ListSecurityPatrols::class)
            ->callAction(TestAction::make('reviewed')->table($patrol));

        $fresh = $patrol->fresh();

        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame([PatrolReviewFlag::ClockAhead->value], $fresh->review_flags);
        $this->assertSame($this->lobby->id, $fresh->security_checkpoint_id);
        $this->assertSame('2026-09-20 01:00', $fresh->submitted_at->format('Y-m-d H:i'));
    }

    private function patrol(SecurityCheckpoint $checkpoint, string $at): SecurityPatrol
    {
        return SecurityPatrol::factory()->create([
            'user_id' => $this->guard->id,
            'security_checkpoint_id' => $checkpoint->id,
            'submitted_at' => $at,
            'created_at' => $at,
        ]);
    }

    private function assess(): int
    {
        return PatrolReview::assess(SecurityPatrol::query()->get());
    }
}
