<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\BookingCalendarWidget;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_app_panel(): void
    {
        $response = $this->get('/app');

        $response->assertRedirect(route('login'));
    }

    public function test_any_authenticated_user_can_access_the_app_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertOk();
    }

    public function test_users_without_the_super_admin_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_super_admins_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        $response = $this->actingAs($user)->get('/admin');

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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
}
