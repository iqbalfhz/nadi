<?php

namespace Tests\Feature;

use App\Filament\Resources\HkInspections\HkInspectionResource;
use App\Filament\Resources\HkInspections\Pages\EditHkInspection;
use App\Filament\Resources\HkInspections\Pages\ViewHkInspection;
use App\Filament\Resources\MessengerDeliveries\MessengerDeliveryResource;
use App\Filament\Resources\MessengerDeliveries\Pages\EditMessengerDelivery;
use App\Filament\Resources\MessengerDeliveries\Pages\ViewMessengerDelivery;
use App\Filament\Resources\ObChecklists\ObChecklistResource;
use App\Filament\Resources\ObChecklists\Pages\EditObChecklist;
use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\Resources\ObChecklists\Pages\ViewObChecklist;
use App\Filament\Resources\SecurityPatrols\Pages\EditSecurityPatrol;
use App\Filament\Resources\SecurityPatrols\Pages\ViewSecurityPatrol;
use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use App\Models\HkInspection;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\SecurityPatrol;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Detail pages for the four field modules in /admin.
 *
 * A report filed from a phone used to be readable only as a truncated table
 * row: the note cut at fifty characters, the photos behind a modal, and no
 * single place showing the whole thing. An admin could not actually read what
 * an officer had reported.
 *
 * What these pages may *change* is deliberately narrow. A field report is a
 * worker's claim plus its evidence — corrections to how it was written down
 * are fair, rewriting what was claimed is not.
 */
class FieldReportDetailAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('internal');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();
    }

    public function test_an_admin_can_read_a_whole_ob_report(): void
    {
        $checklist = ObChecklist::factory()->create([
            'notes' => 'Lantai basah di dekat eskalator, sudah dipel ulang dan dipasang tanda.',
            'submitted_at' => '2026-09-05 23:46:00',
            'created_at' => '2026-09-05 23:48:00',
        ]);

        Livewire::test(ViewObChecklist::class, ['record' => $checklist->getKey()])
            ->assertOk()
            // The note in full, not cut at fifty characters as in the list.
            ->assertSee('Lantai basah di dekat eskalator, sudah dipel ulang dan dipasang tanda.')
            ->assertSee($checklist->area->name)
            ->assertSee($checklist->user->name);
    }

    /**
     * Both times, side by side — this is the one place they belong. The list
     * shows only the reported time on purpose; here the reader has opened a
     * single record, usually to judge whether it can be trusted.
     */
    public function test_the_detail_page_shows_both_times(): void
    {
        $patrol = SecurityPatrol::factory()->create([
            'submitted_at' => '2026-09-06 03:15:00',
            'created_at' => '2026-09-06 07:02:41',
        ]);

        Livewire::test(ViewSecurityPatrol::class, ['record' => $patrol->getKey()])
            ->assertOk()
            ->assertSee('03:15')
            ->assertSee('07:02');
    }

    public function test_an_admin_can_read_a_whole_hk_report(): void
    {
        $inspection = HkInspection::factory()->create([
            'staff_name' => 'Siti Aminah',
            'follow_up' => 'Sudah ditegur dan diarahkan ulang.',
        ]);

        Livewire::test(ViewHkInspection::class, ['record' => $inspection->getKey()])
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('Sudah ditegur dan diarahkan ulang.');
    }

    public function test_an_admin_can_read_a_whole_messenger_task(): void
    {
        $delivery = MessengerDelivery::factory()->create([
            'origin' => 'Front Office Lt 1',
            'destination' => 'Kantor Manajemen Lt 5',
        ]);

        Livewire::test(ViewMessengerDelivery::class, ['record' => $delivery->getKey()])
            ->assertOk()
            ->assertSee('Front Office Lt 1')
            ->assertSee('Kantor Manajemen Lt 5');
    }

    /**
     * The detail page renders the evidence photos itself, which hands out the
     * same signed URLs the modal does — without anyone having to click. It
     * needs the same entry in Riwayat Aktivitas, or the one action that
     * actually exposes the photos becomes the one the log cannot see.
     */
    public function test_opening_a_report_with_photos_is_logged(): void
    {
        $checklist = ObChecklist::factory()->create();
        $checklist->addMedia(UploadedFile::fake()->image('bukti.jpg'))->toMediaCollection('photos');

        Livewire::test(ViewObChecklist::class, ['record' => $checklist->getKey()])->assertOk();

        $this->assertTrue(
            Activity::query()->where('log_name', 'akses-data')->where('description', 'Lihat foto')->exists(),
        );
    }

    /**
     * A report with nothing to expose should not produce an "opened the
     * photos" line. An audit trail full of entries about nothing is one
     * nobody reads.
     */
    public function test_opening_a_report_without_photos_is_not_logged(): void
    {
        $checklist = ObChecklist::factory()->create();

        Livewire::test(ViewObChecklist::class, ['record' => $checklist->getKey()])->assertOk();

        $this->assertFalse(
            Activity::query()->where('log_name', 'akses-data')->exists(),
        );
    }

    public function test_an_admin_can_correct_the_note_on_an_ob_report(): void
    {
        $checklist = ObChecklist::factory()->create(['notes' => 'lantai bash']);

        Livewire::test(EditObChecklist::class, ['record' => $checklist->getKey()])
            ->fillForm(['notes' => 'Lantai basah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Lantai basah', $checklist->fresh()->notes);
    }

    /**
     * The point of the whole edit design: what the worker reported cannot be
     * rewritten from a desk. The evidence fields are not on the form, so a
     * save leaves them exactly as filed.
     */
    public function test_editing_cannot_change_what_the_worker_reported(): void
    {
        $checklist = ObChecklist::factory()->create([
            'notes' => 'catatan awal',
            'submitted_at' => '2026-09-05 23:46:00',
        ]);

        $area = $checklist->ob_area_id;
        $worker = $checklist->user_id;

        Livewire::test(EditObChecklist::class, ['record' => $checklist->getKey()])
            ->fillForm(['notes' => 'catatan diperbaiki'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $checklist->fresh();

        $this->assertSame('catatan diperbaiki', $fresh->notes);
        $this->assertSame($area, $fresh->ob_area_id);
        $this->assertSame($worker, $fresh->user_id);
        $this->assertSame('2026-09-05 23:46', $fresh->submitted_at->format('Y-m-d H:i'));
    }

    public function test_an_admin_can_correct_the_incident_report_on_a_patrol(): void
    {
        $patrol = SecurityPatrol::factory()->create(['incident_report' => null]);

        Livewire::test(EditSecurityPatrol::class, ['record' => $patrol->getKey()])
            ->fillForm(['incident_report' => 'Pintu gudang ditemukan tidak terkunci.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Pintu gudang ditemukan tidak terkunci.', $patrol->fresh()->incident_report);
    }

    /**
     * Tindak lanjut is the reason the HK edit page earns its place: it is
     * genuinely filled in later, by whoever acted on the finding.
     */
    public function test_an_admin_can_record_a_follow_up_on_an_hk_finding(): void
    {
        $inspection = HkInspection::factory()->create(['follow_up' => null]);

        Livewire::test(EditHkInspection::class, ['record' => $inspection->getKey()])
            ->fillForm(['follow_up' => 'Sudah dibersihkan ulang shift berikutnya.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Sudah dibersihkan ulang shift berikutnya.', $inspection->fresh()->follow_up);
    }

    public function test_an_admin_can_correct_a_messenger_request(): void
    {
        $delivery = MessengerDelivery::factory()->create(['destination' => 'Lt 5']);

        Livewire::test(EditMessengerDelivery::class, ['record' => $delivery->getKey()])
            ->fillForm(['destination' => 'Kantor Manajemen Lt 5'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Kantor Manajemen Lt 5', $delivery->fresh()->destination);
    }

    /**
     * A field report created at a desk is a visit that never happened. There
     * is no create page in /admin for any of the four, on purpose.
     */
    public function test_no_field_report_can_be_created_from_admin(): void
    {
        $this->assertFalse(ObChecklistResource::canCreate());
        $this->assertFalse(SecurityPatrolResource::canCreate());
        $this->assertFalse(HkInspectionResource::canCreate());
        $this->assertFalse(MessengerDeliveryResource::canCreate());
    }

    /**
     * The concrete need behind all this: production had rows reading "Test
     * production 1" and "tes aja lah" with no way to remove them.
     */
    public function test_an_admin_can_delete_a_report(): void
    {
        $checklist = ObChecklist::factory()->create(['notes' => 'Test production 1']);

        Livewire::test(ListObChecklists::class)
            ->callAction(TestAction::make('delete')->table($checklist));

        $this->assertDatabaseMissing('ob_checklists', ['id' => $checklist->getKey()]);
    }

    /**
     * Employees hold no admin permissions, and these pages must not be a way
     * around that.
     */
    public function test_an_employee_cannot_open_a_report_detail_page(): void
    {
        $this->seed(ShieldSeeder::class);

        $employee = User::factory()->create();
        $employee->assignRole('karyawan');
        $this->actingAs($employee);

        $this->assertFalse($employee->can('viewAny', ObChecklist::class));
    }
}
