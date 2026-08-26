<?php

namespace Tests\Feature;

use App\Enums\DashboardPeriod;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-27 14:30:00');
    }

    public function test_today_covers_only_today(): void
    {
        [$start, $end] = DashboardPeriod::Today->range();

        $this->assertSame('2026-08-27 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-27 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_the_rolling_presets_include_today_in_their_count(): void
    {
        // "7 Hari Terakhir" means today plus the six before it, not today
        // plus seven — otherwise the window silently reports eight days.
        [$start, $end] = DashboardPeriod::Last7Days->range();

        $this->assertSame('2026-08-21', $start->toDateString());
        $this->assertSame('2026-08-27', $end->toDateString());

        [$start, $end] = DashboardPeriod::Last30Days->range();

        $this->assertSame('2026-07-29', $start->toDateString());
        $this->assertSame('2026-08-27', $end->toDateString());
    }

    public function test_in_progress_periods_stop_at_today_instead_of_the_end_of_the_calendar_period(): void
    {
        // Running to 31 Aug / 31 Dec would leave every chart trailing off
        // into empty future buckets for the rest of the month/year.
        [$start, $end] = DashboardPeriod::ThisMonth->range();

        $this->assertSame('2026-08-01', $start->toDateString());
        $this->assertSame('2026-08-27', $end->toDateString());

        [$start, $end] = DashboardPeriod::ThisYear->range();

        $this->assertSame('2026-01-01', $start->toDateString());
        $this->assertSame('2026-08-27', $end->toDateString());
    }

    public function test_last_month_covers_the_whole_previous_month(): void
    {
        [$start, $end] = DashboardPeriod::LastMonth->range();

        $this->assertSame('2026-07-01', $start->toDateString());
        $this->assertSame('2026-07-31', $end->toDateString());
    }

    public function test_a_custom_range_uses_the_supplied_dates(): void
    {
        [$start, $end] = DashboardPeriod::Custom->range('2026-03-05', '2026-03-09');

        $this->assertSame('2026-03-05 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-09 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_an_inverted_custom_range_is_swapped_rather_than_returning_nothing(): void
    {
        [$start, $end] = DashboardPeriod::Custom->range('2026-03-09', '2026-03-05');

        $this->assertSame('2026-03-05', $start->toDateString());
        $this->assertSame('2026-03-09', $end->toDateString());
    }

    public function test_a_malformed_custom_date_falls_back_instead_of_crashing_the_dashboard(): void
    {
        // The filter state lives in the URL query string, so anyone can type
        // anything into it — this must degrade, not throw.
        [$start, $end] = DashboardPeriod::Custom->range('not-a-date', '');

        $this->assertSame('2026-08-01', $start->toDateString());
        $this->assertSame('2026-08-27', $end->toDateString());
    }

    public function test_every_case_is_offered_as_a_labelled_option(): void
    {
        $options = DashboardPeriod::options();

        $this->assertCount(count(DashboardPeriod::cases()), $options);
        $this->assertSame('Bulan Ini', $options[DashboardPeriod::ThisMonth->value]);
    }
}
