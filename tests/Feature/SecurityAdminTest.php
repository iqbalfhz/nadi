<?php

namespace Tests\Feature;

use App\Filament\Resources\SecurityCheckpoints\Pages\CreateSecurityCheckpoint;
use App\Filament\Resources\SecurityCheckpoints\SecurityCheckpointResource;
use App\Filament\Resources\SecurityPatrols\Pages\ListSecurityPatrols;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_admin_can_create_a_security_checkpoint_with_an_auto_generated_code(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateSecurityCheckpoint::class)
            ->fillForm([
                'name' => 'Pintu Belakang Gudang',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(SecurityCheckpointResource::getUrl('index'));

        $checkpoint = SecurityCheckpoint::query()->where('name', 'Pintu Belakang Gudang')->firstOrFail();

        $this->assertNotEmpty($checkpoint->code);
    }

    public function test_the_qr_code_route_requires_authorization(): void
    {
        $checkpoint = SecurityCheckpoint::factory()->create();

        $this->get(route('security-checkpoints.qr-code', $checkpoint))
            ->assertRedirect(); // unauthenticated — redirected to login
    }

    public function test_an_admin_can_download_the_checkpoint_qr_code(): void
    {
        $this->actingAsSuperAdmin();
        $checkpoint = SecurityCheckpoint::factory()->create();

        $response = $this->get(route('security-checkpoints.qr-code', $checkpoint));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_the_admin_patrol_report_shows_submissions_from_every_user(): void
    {
        $this->actingAsSuperAdmin();

        $patrolA = SecurityPatrol::factory()->create();
        $patrolB = SecurityPatrol::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(ListSecurityPatrols::class)
            ->assertCanSeeTableRecords([$patrolA, $patrolB]);
    }
}
