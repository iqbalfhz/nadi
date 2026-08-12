<?php

namespace Tests\Feature;

use App\Livewire\Queue\KioskPinGate;
use App\Livewire\Queue\KioskTake;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Settings\QueueKioskSettings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;
use Tests\TestCase;

class QueueKioskTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_kiosk_redirects_to_the_pin_gate_when_not_unlocked(): void
    {
        $response = $this->get(route('queue.kiosk.take'));

        $response->assertRedirect(route('queue.kiosk.pin'));
    }

    public function test_entering_the_correct_pin_unlocks_the_kiosk(): void
    {
        Livewire::test(KioskPinGate::class)
            ->set('pin', '123456')
            ->call('verify')
            ->assertRedirect(route('queue.kiosk.take'));

        $this->assertTrue(Cookie::hasQueued('queue_kiosk_unlocked'));
    }

    public function test_entering_the_wrong_pin_shows_an_error_and_does_not_unlock(): void
    {
        Livewire::test(KioskPinGate::class)
            ->set('pin', 'wrong-pin')
            ->call('verify')
            ->assertHasErrors('pin');

        $this->assertFalse(Cookie::hasQueued('queue_kiosk_unlocked'));
    }

    public function test_repeated_wrong_pin_attempts_get_rate_limited(): void
    {
        $component = Livewire::test(KioskPinGate::class);

        foreach (range(1, 6) as $attempt) {
            $component->set('pin', 'wrong-pin')->call('verify');
        }

        $component->assertHasErrors('pin');
        $this->assertFalse(Cookie::hasQueued('queue_kiosk_unlocked'));
    }

    public function test_disabling_the_kiosk_blocks_access_even_with_a_previously_unlocked_cookie(): void
    {
        $validCookie = hash('sha256', app(QueueKioskSettings::class)->pin);

        $settings = app(QueueKioskSettings::class);
        $settings->is_enabled = false;
        $settings->save();

        $response = $this->withCookie('queue_kiosk_unlocked', $validCookie)
            ->get(route('queue.kiosk.take'));

        $response->assertRedirect(route('queue.kiosk.pin'));
    }

    public function test_disabling_the_kiosk_hides_the_pin_form_and_rejects_verification(): void
    {
        $settings = app(QueueKioskSettings::class);
        $settings->is_enabled = false;
        $settings->save();

        Livewire::test(KioskPinGate::class)
            ->assertSet('isEnabled', false)
            ->set('pin', '123456')
            ->call('verify')
            ->assertHasErrors('pin');

        $this->assertFalse(Cookie::hasQueued('queue_kiosk_unlocked'));
    }

    public function test_rotating_the_pin_invalidates_a_previously_unlocked_cookie(): void
    {
        $staleCookie = hash('sha256', app(QueueKioskSettings::class)->pin);

        $settings = app(QueueKioskSettings::class);
        $settings->pin = 'new-secret-pin';
        $settings->save();

        $response = $this->withCookie('queue_kiosk_unlocked', $staleCookie)
            ->get(route('queue.kiosk.take'));

        $response->assertRedirect(route('queue.kiosk.pin'));
    }

    public function test_taking_a_ticket_creates_a_waiting_ticket(): void
    {
        $category = QueueCategory::factory()->create(['is_active' => true]);

        Livewire::test(KioskTake::class)
            ->call('take', $category->id)
            ->assertSet('issuedTicketId', fn (?int $id) => $id !== null);

        $this->assertDatabaseHas('queue_tickets', [
            'queue_category_id' => $category->id,
            'number' => 1,
        ]);
    }

    public function test_taking_a_ticket_for_an_inactive_category_fails(): void
    {
        $category = QueueCategory::factory()->create(['is_active' => false]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(KioskTake::class)->call('take', $category->id);
    }

    public function test_dismissing_the_issued_ticket_returns_to_the_category_grid(): void
    {
        $category = QueueCategory::factory()->create(['is_active' => true]);
        $ticket = QueueTicket::createNextFor($category);

        Livewire::test(KioskTake::class)
            ->set('issuedTicketId', $ticket->id)
            ->call('dismissTicket')
            ->assertSet('issuedTicketId', null);
    }
}
