<?php

namespace Tests\Feature;

use App\Filament\Resources\HkInspections\Pages\ListHkInspections;
use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\Resources\SecurityPatrols\Pages\ListSecurityPatrols;
use App\Models\HkInspection;
use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * What a supervisor actually reads in /admin.
 *
 * The backend has stored submitted_at since 3 September, but no panel showed
 * it — so a 03:15 dawn round flushed at 07:00 read as a guard who started at
 * seven, and every report in a returning outbox stamped the same minute. The
 * data was there; the path to the eyes that needed it was not.
 */
class FieldReportTimeAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
    }

    public function test_the_ob_report_shows_when_the_worker_filed_it_not_when_it_arrived(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-05 23:58:00',
            'created_at' => '2026-09-06 00:21:18',
        ]);

        Livewire::test(ListObChecklists::class)
            ->assertCanSeeTableRecords([$checklist])
            ->assertTableColumnStateSet('submitted_at', $checklist->submitted_at, $checklist);
    }

    public function test_the_patrol_report_shows_when_the_guard_reached_the_post(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'submitted_at' => '2026-09-06 03:15:00',
            'created_at' => '2026-09-06 07:02:41',
        ]);

        Livewire::test(ListSecurityPatrols::class)
            ->assertCanSeeTableRecords([$patrol])
            ->assertTableColumnStateSet('submitted_at', $patrol->submitted_at, $patrol);
    }

    public function test_the_hk_report_shows_when_the_inspection_happened(): void
    {
        $inspection = HkInspection::factory()->create([
            'submitted_at' => '2026-09-05 22:40:00',
            'created_at' => '2026-09-06 06:10:00',
        ]);

        Livewire::test(ListHkInspections::class)
            ->assertCanSeeTableRecords([$inspection])
            ->assertTableColumnStateSet('submitted_at', $inspection->submitted_at, $inspection);
    }

    /**
     * Older reports, and anything filed from the web, have no submitted_at.
     * They must still show a time rather than a dash — and must say which
     * time it is, so a server clock is never read as the worker's own claim.
     */
    public function test_a_report_without_a_filed_time_falls_back_to_the_arrival_time(): void
    {
        $checklist = ObChecklist::factory()->create([
            'submitted_at' => null,
            'created_at' => '2026-09-01 08:30:00',
        ]);

        Livewire::test(ListObChecklists::class)
            ->assertTableColumnStateSet('submitted_at', $checklist->created_at, $checklist)
            ->assertSee('waktu terima server');
    }

    /**
     * The reason the whole offline chain exists: twelve reports flushed in the
     * same second, ordered by when the work was done, not by when the signal
     * came back. Their created_at order here is the reverse of their real one.
     */
    public function test_a_returning_outbox_is_listed_in_the_order_the_work_was_done(): void
    {
        $first = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-06 03:10:00',
            'created_at' => '2026-09-06 07:00:03',
        ]);
        $second = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-06 04:25:00',
            'created_at' => '2026-09-06 07:00:02',
        ]);
        $third = ObChecklist::factory()->create([
            'submitted_at' => '2026-09-06 05:40:00',
            'created_at' => '2026-09-06 07:00:01',
        ]);

        Livewire::test(ListObChecklists::class)
            ->assertCanSeeTableRecords([$third, $second, $first], inOrder: true);
    }

    /**
     * A patrol walked at 23:58 belongs to the night it was walked, even though
     * the server only heard about it after midnight. Filtering on created_at
     * hid it from that day and showed it on the next one.
     */
    public function test_the_date_filter_follows_the_night_the_work_was_done(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'submitted_at' => '2026-09-05 23:58:00',
            'created_at' => '2026-09-06 00:21:18',
        ]);

        Livewire::test(ListSecurityPatrols::class)
            ->filterTable('reported_at', ['from' => '2026-09-05', 'until' => '2026-09-05'])
            ->assertCanSeeTableRecords([$patrol]);

        Livewire::test(ListSecurityPatrols::class)
            ->filterTable('reported_at', ['from' => '2026-09-06', 'until' => '2026-09-06'])
            ->assertCanNotSeeTableRecords([$patrol]);
    }
}
