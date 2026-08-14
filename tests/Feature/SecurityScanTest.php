<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SecurityScan;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Storage::fake('public');
    }

    public function test_a_guard_can_submit_a_patrol_report_by_scanning_a_valid_checkpoint_code(): void
    {
        $guard = User::factory()->create();
        $this->actingAs($guard);

        $checkpoint = SecurityCheckpoint::factory()->create(['is_active' => true]);

        Livewire::test(SecurityScan::class, ['code' => $checkpoint->code])
            ->fillForm([
                'photos' => [UploadedFile::fake()->image('temuan.jpg')],
                'incident_report' => 'Pintu darurat terkunci.',
            ])
            ->call('submit');

        $patrol = SecurityPatrol::query()->where('security_checkpoint_id', $checkpoint->id)->firstOrFail();

        $this->assertSame($guard->id, $patrol->user_id);
        $this->assertSame('Pintu darurat terkunci.', $patrol->incident_report);
        $this->assertCount(1, $patrol->getMedia('photos'));
    }

    public function test_an_inactive_checkpoints_code_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $checkpoint = SecurityCheckpoint::factory()->create(['is_active' => false]);

        Livewire::test(SecurityScan::class, ['code' => $checkpoint->code])
            ->assertSet('checkpoint', null);

        $this->assertDatabaseCount('security_patrols', 0);
    }

    public function test_an_unknown_code_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SecurityScan::class, ['code' => 'not-a-real-code'])
            ->assertSet('checkpoint', null);
    }
}
