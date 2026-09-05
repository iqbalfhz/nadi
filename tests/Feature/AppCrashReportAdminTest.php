<?php

namespace Tests\Feature;

use App\Filament\Resources\AppCrashReports\Pages\ListAppCrashReports;
use App\Models\AppCrashReport;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reading the crash reports in /admin. Without this page the endpoint is a
 * table nobody opens, which is the same as not having it.
 */
class AppCrashReportAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_admin_can_read_the_crash_reports(): void
    {
        $this->actingAsSuperAdmin();

        $crash = $this->crash(['message' => 'Null check operator used on a null value']);

        Livewire::test(ListAppCrashReports::class)
            ->assertCanSeeTableRecords([$crash])
            ->assertSee('Null check operator used on a null value');
    }

    public function test_the_newest_failure_is_listed_first(): void
    {
        $this->actingAsSuperAdmin();

        $old = $this->crash([
            'fingerprint' => str_repeat('a', 64),
            'last_occurred_at' => '2026-08-01 09:00:00',
        ]);
        $recent = $this->crash([
            'fingerprint' => str_repeat('b', 64),
            'last_occurred_at' => '2026-09-05 22:15:00',
        ]);

        Livewire::test(ListAppCrashReports::class)
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true);
    }

    /**
     * Nothing writes these rows but the handsets, and nobody should be able to
     * tidy away the evidence that the app is failing in the field.
     */
    public function test_the_page_is_read_only(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $crash = $this->crash();

        $this->assertFalse($admin->can('create', AppCrashReport::class));
        $this->assertFalse($admin->can('update', $crash));
        $this->assertFalse($admin->can('delete', $crash));
    }

    public function test_an_employee_without_the_permission_cannot_read_them(): void
    {
        $this->seed(ShieldSeeder::class);

        $employee = User::factory()->create();
        $employee->assignRole('karyawan');
        $this->actingAs($employee);

        $this->assertFalse($employee->can('viewAny', AppCrashReport::class));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function crash(array $attributes = []): AppCrashReport
    {
        return AppCrashReport::query()->create([
            'fingerprint' => str_repeat('c', 64),
            'app_version' => '1.0.3+4',
            'message' => 'Null check operator used on a null value',
            'stack' => '#0 _PatrolFormScreenState._pindai',
            'platform' => 'android',
            'device' => 'Xiaomi 24115RA8EG',
            'os_version' => '16',
            'first_occurred_at' => '2026-09-05 22:15:00',
            'last_occurred_at' => '2026-09-05 22:15:00',
            ...$attributes,
        ]);
    }
}
