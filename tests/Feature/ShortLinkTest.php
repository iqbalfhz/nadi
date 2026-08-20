<?php

namespace Tests\Feature;

use App\Filament\App\Resources\ShortLinks\Pages\CreateShortLink;
use App\Filament\App\Resources\ShortLinks\Pages\ListShortLinks as AppListShortLinks;
use App\Filament\Resources\ShortLinks\Pages\ListShortLinks as AdminListShortLinks;
use App\Models\ShortLink;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_code_is_auto_generated_and_unique(): void
    {
        $one = ShortLink::factory()->create();
        $two = ShortLink::factory()->create();

        $this->assertNotEmpty($one->code);
        $this->assertNotEmpty($two->code);
        $this->assertNotSame($one->code, $two->code);
    }

    public function test_the_short_url_attribute_points_at_the_redirect_route(): void
    {
        $shortLink = ShortLink::factory()->create();

        $this->assertSame(url("/s/{$shortLink->code}"), $shortLink->short_url);
    }

    public function test_visiting_a_valid_code_redirects_to_the_target_and_records_a_click(): void
    {
        $shortLink = ShortLink::factory()->create(['target_url' => 'https://example.com/dokumen-panjang']);

        $response = $this->get("/s/{$shortLink->code}");

        $response->assertRedirect('https://example.com/dokumen-panjang');
        $this->assertSame(1, $shortLink->fresh()->clicks);
        $this->assertNotNull($shortLink->fresh()->last_clicked_at);
    }

    public function test_visiting_an_unknown_code_returns_a_404(): void
    {
        $response = $this->get('/s/tidak-ada');

        $response->assertNotFound();
    }

    public function test_an_employee_with_permission_can_create_a_short_link(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        $user = $this->actingAsEmployeeWithPermissions(['ViewAny:ShortLink', 'Create:ShortLink']);

        Livewire::test(CreateShortLink::class)
            ->fillForm(['target_url' => 'https://example.com/panjang-sekali'])
            ->call('create')
            ->assertHasNoFormErrors();

        $shortLink = ShortLink::query()->where('target_url', 'https://example.com/panjang-sekali')->firstOrFail();

        $this->assertSame($user->id, $shortLink->created_by);
    }

    public function test_the_app_list_only_shows_the_current_users_own_short_links(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:ShortLink');

        $mine = ShortLink::factory()->create(['created_by' => $me->id]);
        $theirs = ShortLink::factory()->create(['created_by' => $someoneElse->id]);

        Livewire::test(AppListShortLinks::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_the_admin_list_shows_short_links_from_every_employee(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        $linkA = ShortLink::factory()->create();
        $linkB = ShortLink::factory()->create(['created_by' => User::factory()->create()->id]);

        Livewire::test(AdminListShortLinks::class)
            ->assertCanSeeTableRecords([$linkA, $linkB]);
    }
}
