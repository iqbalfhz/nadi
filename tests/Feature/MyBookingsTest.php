<?php

namespace Tests\Feature;

use App\Filament\App\Resources\RoomBookings\Pages\ListRoomBookings;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyBookingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_the_list_only_shows_the_current_users_own_bookings(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');
        $otherUser = User::factory()->create();
        $room = Room::factory()->create();

        $ownBooking = RoomBooking::factory()->create(['room_id' => $room->id, 'user_id' => $user->id]);
        RoomBooking::factory()->create(['room_id' => $room->id, 'user_id' => $otherUser->id]);

        Livewire::actingAs($user)
            ->test(ListRoomBookings::class)
            ->assertCanSeeTableRecords([$ownBooking])
            ->assertCountTableRecords(1);
    }

    public function test_an_employee_can_cancel_their_own_booking(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create(['room_id' => $room->id, 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ListRoomBookings::class)
            ->callTableAction(DeleteAction::class, $booking);

        $this->assertSoftDeleted($booking);
    }

    public function test_an_employee_cannot_cancel_someone_elses_booking(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create(['room_id' => $room->id, 'user_id' => $otherUser->id]);

        $this->assertTrue($user->cannot('delete', $booking));
    }

    public function test_super_admins_can_still_cancel_any_booking_via_the_policy(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create(['room_id' => $room->id]);

        $this->assertTrue($superAdmin->can('delete', $booking));
    }
}
