<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\BookingCalendarWidget;
use App\Filament\Resources\Areas\AreaResource;
use App\Models\Area;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_app_panel(): void
    {
        $response = $this->get('/app');

        $response->assertRedirect(route('login'));
    }

    public function test_a_user_with_no_app_relevant_permissions_is_rejected_from_the_app_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertForbidden();
    }

    public function test_a_user_with_at_least_one_app_relevant_permission_can_access_the_app_panel(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        $response = $this->get('/app');

        $response->assertOk();
    }

    public function test_a_user_with_no_admin_permissions_can_enter_the_panel_but_not_a_gated_resource(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertOk();

        // Panel entry is only gated by is_active — each resource's own
        // Shield policy is what actually restricts what's visible/usable.
        $response = $this->actingAs($user)->get(AreaResource::getUrl('index'));
        $response->assertForbidden();
    }

    public function test_super_admins_can_access_the_admin_panel(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get('/admin');

        $response->assertOk();
    }

    public function test_it_detects_overlapping_bookings_for_the_same_room(): void
    {
        $room = Room::factory()->create();

        $existing = RoomBooking::factory()->create([
            'room_id' => $room->id,
            'starts_at' => '2026-09-01 10:00:00',
            'ends_at' => '2026-09-01 11:00:00',
        ]);

        $this->assertTrue(RoomBooking::overlaps($room->id, now()->parse('2026-09-01 10:30:00'), now()->parse('2026-09-01 11:30:00')));
        $this->assertFalse(RoomBooking::overlaps($room->id, now()->parse('2026-09-01 11:00:00'), now()->parse('2026-09-01 12:00:00')));
        $this->assertFalse(RoomBooking::overlaps($room->id, now()->parse('2026-09-01 10:30:00'), now()->parse('2026-09-01 11:30:00'), excludingId: $existing->id));
    }

    public function test_cancelling_a_booking_frees_the_room(): void
    {
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create([
            'room_id' => $room->id,
            'starts_at' => '2026-09-01 10:00:00',
            'ends_at' => '2026-09-01 11:00:00',
        ]);

        $booking->delete();

        $this->assertFalse(RoomBooking::overlaps($room->id, now()->parse('2026-09-01 10:00:00'), now()->parse('2026-09-01 11:00:00')));
    }

    public function test_an_employee_can_create_a_booking_through_the_calendar_widget_action(): void
    {
        $user = $this->actingAsEmployeeWithPermissions(['View:BookingCalendarWidget', 'Create:RoomBooking']);
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->mountAction('createRoomBooking')
            ->setActionData([
                'room_id' => $room->id,
                'title' => 'Rapat Tim',
                'starts_at' => '2026-09-01 09:00:00',
                'ends_at' => '2026-09-01 10:00:00',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('room_bookings', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'title' => 'Rapat Tim',
        ]);
    }

    public function test_the_calendar_widget_action_rejects_an_overlapping_booking(): void
    {
        $user = $this->actingAsEmployeeWithPermissions(['View:BookingCalendarWidget', 'Create:RoomBooking']);
        $room = Room::factory()->create();

        RoomBooking::factory()->create([
            'room_id' => $room->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);

        Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->mountAction('createRoomBooking')
            ->setActionData([
                'room_id' => $room->id,
                'title' => 'Rapat Bentrok',
                'starts_at' => '2026-09-01 09:30:00',
                'ends_at' => '2026-09-01 10:30:00',
            ])
            ->callMountedAction()
            ->assertHasActionErrors(['ends_at']);

        $this->assertDatabaseMissing('room_bookings', [
            'title' => 'Rapat Bentrok',
        ]);
    }

    public function test_the_calendar_widget_defaults_to_a_daily_view_starting_at_7am(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('View:BookingCalendarWidget');

        $component = Livewire::actingAs($user)->test(BookingCalendarWidget::class);

        $options = $component->instance()->getOptions();

        $this->assertSame('resourceTimeGridDay', $component->instance()->getCalendarView()->value);
        $this->assertSame('07:00:00', $options['slotMinTime']);
        // The underlying event-calendar library uses start/center/end, not FullCalendar's left/right.
        $this->assertSame('resourceTimeGridDay,resourceTimeGridWeek,resourceTimelineMonth', $options['headerToolbar']['end']);
    }

    public function test_the_widget_renders_the_area_filter_and_mini_calendar(): void
    {
        $area = Area::factory()->create(['name' => 'Basement 2']);
        $user = $this->actingAsEmployeeWithPermissions('View:BookingCalendarWidget');

        Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->assertSee('Basement 2')
            ->assertSee('Semua Lokasi')
            ->assertSeeHtml('wire:click="miniCalendarPreviousMonth"')
            ->assertSeeHtml('wire:click="miniCalendarNextMonth"');
    }

    public function test_filtering_by_area_only_returns_rooms_and_bookings_for_that_area(): void
    {
        $areaA = Area::factory()->create();
        $areaB = Area::factory()->create();
        $roomA = Room::factory()->create(['area_id' => $areaA->id]);
        $roomB = Room::factory()->create(['area_id' => $areaB->id]);

        $user = $this->actingAsEmployeeWithPermissions('View:BookingCalendarWidget');

        $component = Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->set('areaId', $areaA->id);

        $resourceIds = $component->instance()->getResourcesJs();
        $resourceIds = collect($resourceIds)->pluck('id')->all();

        $this->assertContains((string) $roomA->id, $resourceIds);
        $this->assertNotContains((string) $roomB->id, $resourceIds);
    }

    public function test_mini_calendar_builds_full_weeks_covering_the_selected_month(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('View:BookingCalendarWidget');

        $component = Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->set('miniCalendarMonth', '2026-02-01');

        $weeks = $component->instance()->getMiniCalendarWeeks();

        foreach ($weeks as $week) {
            $this->assertCount(7, $week);
        }

        $allDays = collect($weeks)->flatten();
        $this->assertTrue($allDays->contains(fn ($date) => $date->toDateString() === '2026-02-01'));
        $this->assertTrue($allDays->contains(fn ($date) => $date->toDateString() === '2026-02-28'));
    }

    public function test_a_single_click_on_a_date_cell_prefills_and_creates_a_booking(): void
    {
        $user = $this->actingAsEmployeeWithPermissions(['View:BookingCalendarWidget', 'Create:RoomBooking']);
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(BookingCalendarWidget::class)
            ->call('onDateClickJs', [
                // A Jakarta browser (UTC+7) clicking "09:00 local" sends this as UTC in
                // the ISO string, plus its own offset in minutes (tzOffset: 420).
                'date' => '2026-09-01T02:00:00Z',
                'allDay' => false,
                'view' => [
                    'type' => 'resourceTimeGridDay',
                    'title' => 'September 1, 2026',
                    'currentStart' => '2026-09-01T00:00:00Z',
                    'currentEnd' => '2026-09-02T00:00:00Z',
                    'activeStart' => '2026-09-01T00:00:00Z',
                    'activeEnd' => '2026-09-02T00:00:00Z',
                ],
                'resource' => [
                    'id' => (string) $room->id,
                    'title' => $room->name,
                ],
                'tzOffset' => 420,
            ])
            ->setActionData(['title' => 'Booking Klik Tunggal'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        // Stored value must be Jakarta wall-clock time (09:00), matching what the
        // admin panel displays — not the raw UTC instant the browser sent (02:00).
        $this->assertDatabaseHas('room_bookings', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'title' => 'Booking Klik Tunggal',
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);
    }

    public function test_a_stored_booking_is_redisplayed_on_the_calendar_at_the_same_local_time(): void
    {
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create([
            'room_id' => $room->id,
            'title' => 'Sinkron Check',
            // Wall-clock 20:00 Jakarta, as an admin/calendar user would read it.
            'starts_at' => '2026-09-01 20:00:00',
            'ends_at' => '2026-09-01 21:00:00',
        ]);

        $event = $booking->toCalendarEvent()->toCalendarObject(timezoneOffset: 420, useFilamentTimezone: true);

        // The calendar must be told 20:00+07:00, the same moment the admin panel shows —
        // not a bare "20:00Z" that a browser would then shift by its own offset again.
        $this->assertSame('2026-09-01T20:00:00+07:00', $event['start']);
    }
}
