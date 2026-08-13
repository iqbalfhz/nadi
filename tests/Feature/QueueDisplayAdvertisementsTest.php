<?php

namespace Tests\Feature;

use App\Livewire\Queue\Display;
use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueDisplayAdvertisementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_advertisements_are_shown_on_the_display(): void
    {
        $active = Advertisement::factory()->create(['is_active' => true]);
        $inactive = Advertisement::factory()->create(['is_active' => false]);

        $ids = Livewire::test(Display::class)->instance()->advertisements()->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_advertisements_are_ordered_by_sort_order(): void
    {
        $second = Advertisement::factory()->create(['is_active' => true, 'sort_order' => 2]);
        $first = Advertisement::factory()->create(['is_active' => true, 'sort_order' => 1]);

        $ids = Livewire::test(Display::class)->instance()->advertisements()->pluck('id')->values();

        $this->assertSame([$first->id, $second->id], $ids->all());
    }
}
