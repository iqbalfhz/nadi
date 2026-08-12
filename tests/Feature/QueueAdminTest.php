<?php

namespace Tests\Feature;

use App\Filament\Resources\QueueCategories\Pages\CreateQueueCategory;
use App\Filament\Resources\QueueCategories\Pages\EditQueueCategory;
use App\Filament\Resources\QueueCategories\QueueCategoryResource;
use App\Filament\Resources\QueueTickets\QueueTicketResource;
use App\Models\QueueCategory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_creating_a_queue_category_redirects_back_to_the_list(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateQueueCategory::class)
            ->fillForm([
                'name' => 'Resepsionis',
                'code' => 'a',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(QueueCategoryResource::getUrl('index'));

        $this->assertDatabaseHas('queue_categories', [
            'name' => 'Resepsionis',
            'code' => 'A',
        ]);
    }

    public function test_queue_category_code_is_stored_uppercase(): void
    {
        $this->actingAsSuperAdmin();
        $category = QueueCategory::factory()->create(['code' => 'A']);

        Livewire::test(EditQueueCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => $category->name,
                'code' => 'b',
                'is_active' => $category->is_active,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('B', $category->fresh()->code);
    }

    public function test_the_ticket_history_resource_has_no_create_capability(): void
    {
        $this->assertFalse(QueueTicketResource::canCreate());
        $this->assertArrayNotHasKey('create', QueueTicketResource::getPages());
    }
}
