<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageQueueKioskSettings;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Event;
use App\Models\ObChecklist;
use App\Models\QueueCategory;
use App\Models\QueueTicket;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Settings\QueueKioskSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_who_changed_what_with_the_old_and_new_value(): void
    {
        $user = $this->actingAsSuperAdmin();

        $area = Area::create(['name' => 'Lantai 1']);
        $area->update(['name' => 'Lantai 2']);

        $update = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();

        $this->assertSame('Ubah Lokasi', $update->description);
        $this->assertSame($user->id, $update->causer_id);
        $this->assertSame('Lantai 1', $update->attribute_changes['old']['name']);
        $this->assertSame('Lantai 2', $update->attribute_changes['attributes']['name']);
    }

    public function test_an_update_that_changes_nothing_is_not_recorded(): void
    {
        $this->actingAsSuperAdmin();

        $area = Area::create(['name' => 'Lantai 1']);

        $before = Activity::count();
        $area->update(['name' => 'Lantai 1']);

        $this->assertSame($before, Activity::count(), 'A no-op save must not add a line to the log.');
    }

    public function test_deleting_records_what_was_there_before(): void
    {
        $this->actingAsSuperAdmin();

        $area = Area::create(['name' => 'Gudang']);
        $area->delete();

        $deleted = Activity::query()->where('event', 'deleted')->latest('id')->firstOrFail();

        $this->assertSame('Hapus Lokasi', $deleted->description);
        $this->assertSame('Gudang', $deleted->attribute_changes['old']['name']);
    }

    /**
     * Queue tickets, event tickets and bazaar line items are written many
     * times a day and already carry who and when — logging their creation
     * would bury every edit worth reading.
     */
    public function test_high_volume_records_log_edits_but_not_creation(): void
    {
        $this->actingAsSuperAdmin();

        $category = QueueCategory::create(['name' => 'Loket A', 'code' => 'A', 'is_active' => true]);

        $before = Activity::count();
        $ticket = QueueTicket::create(['queue_category_id' => $category->id, 'number' => 1]);
        $this->assertSame($before, Activity::count(), 'Taking a queue number must not write a log line.');

        $ticket->update(['counter_label' => 'Loket 3']);
        $this->assertSame($before + 1, Activity::count(), 'Editing one afterwards must.');
    }

    /**
     * The single most important property of this feature: an activity log
     * stores old and new values verbatim, so without redaction an ordinary
     * edit would write the password hash, the 2FA secret, the kiosk PIN or
     * the Google Drive token into a table built for admins to read.
     */
    public function test_secrets_never_reach_the_log(): void
    {
        $this->actingAsSuperAdmin();

        $user = User::factory()->create();
        $user->update(['password' => 'rahasia-sekali-123', 'name' => 'Nama Baru']);

        $activity = Activity::query()->where('subject_type', User::class)->latest('id')->firstOrFail();

        $recorded = json_encode($activity->attribute_changes);

        $this->assertStringNotContainsString('rahasia-sekali-123', (string) $recorded);
        $this->assertArrayNotHasKey('password', $activity->attribute_changes['attributes'] ?? []);
        $this->assertArrayNotHasKey('password', $activity->attribute_changes['old'] ?? []);

        // ...while the harmless change beside it is still recorded.
        $this->assertSame('Nama Baru', $activity->attribute_changes['attributes']['name']);
    }

    public function test_it_records_signing_in_and_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        event(new Login('web', $user, false));

        $login = Activity::query()->where('log_name', 'akses')->latest('id')->firstOrFail();

        $this->assertSame('Login berhasil', $login->description);
        $this->assertSame($user->id, $login->causer_id);
    }

    /**
     * A failed login has no signed-in user, so the attempted address is the
     * only thing that makes the entry useful — and the password must not be
     * anywhere near it.
     */
    public function test_it_records_a_failed_login_with_the_attempted_address_only(): void
    {
        event(new Failed('web', null, [
            'email' => 'penyusup@example.test',
            'password' => 'tebakan-rahasia',
        ]));

        $failed = Activity::query()->where('log_name', 'akses')->latest('id')->firstOrFail();

        $this->assertSame('Login gagal', $failed->description);
        $this->assertSame('penyusup@example.test', $failed->getProperty('email'));
        $this->assertStringNotContainsString('tebakan-rahasia', (string) json_encode($failed->properties));
    }

    public function test_it_records_a_role_being_granted(): void
    {
        $admin = $this->actingAsSuperAdmin();

        $employee = User::factory()->create();
        $employee->assignRole('super_admin');

        $granted = Activity::query()->where('log_name', 'izin')->latest('id')->firstOrFail();

        $this->assertSame('Role diberikan', $granted->description);
        $this->assertSame($employee->id, $granted->subject_id, 'Subject is who gained the access...');
        $this->assertSame($admin->id, $granted->causer_id, '...causer is who granted it.');
        $this->assertContains('super_admin', $granted->getProperty('items'));
    }

    /**
     * Settings aren't Eloquent models, so they have no model event to hang
     * off — and they hold the most sensitive values in the app.
     */
    public function test_a_settings_change_records_the_field_names_but_not_the_values(): void
    {
        $user = $this->actingAsSuperAdmin();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(ManageQueueKioskSettings::class)
            ->fillForm(['pin' => '778899', 'is_enabled' => true])
            ->call('save');

        $logged = Activity::query()->where('log_name', 'sistem')->latest('id')->firstOrFail();

        $this->assertSame('Ubah Pengaturan Kiosk Antrian', $logged->description);
        $this->assertContains('pin', $logged->getProperty('kolom_diubah'));
        $this->assertStringNotContainsString('778899', (string) json_encode($logged->properties));
        $this->assertSame('778899', app(QueueKioskSettings::class)->pin, 'The setting itself still saved.');
    }

    public function test_the_page_is_gated_and_offers_no_way_to_edit_the_log(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs(User::factory()->create());
        $this->get(ActivityLogResource::getUrl('index'))->assertForbidden();

        $this->actingAsSuperAdmin();
        $this->get(ActivityLogResource::getUrl('index'))->assertOk();

        // An audit trail its own subjects can edit records nothing worth
        // trusting, so there is no page to do it from.
        $this->assertSame(['index'], array_keys(ActivityLogResource::getPages()));
        $this->assertFalse(ActivityLogResource::canCreate());

        $activity = Activity::query()->latest('id')->firstOrFail();
        $policy = new ActivityLogPolicy;
        $log = ActivityLog::query()->find($activity->id);

        $this->assertFalse($policy->update(Auth::user(), $log));
        $this->assertFalse($policy->delete(Auth::user(), $log));
    }

    public function test_a_subject_is_shown_by_a_name_an_admin_recognises(): void
    {
        $this->actingAsSuperAdmin();

        $event = Event::factory()->create(['name' => 'Nobar Kemerdekaan']);
        $event->update(['regular_price' => 30000]);

        $activity = ActivityLog::query()
            ->where('subject_type', Event::class)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('Event — Nobar Kemerdekaan', $activity->subjectLabel());
    }

    /**
     * Laravel auto-registers every `handle*` method it finds in app/Listeners.
     * LogAccessActivity also registers itself explicitly, so methods named
     * handleLogin() would be bound twice and every sign-in would appear in the
     * log twice over — which is exactly what shipped the first time.
     */
    public function test_one_login_writes_exactly_one_line(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $before = Activity::count();
        event(new Login('web', $user, false));

        $this->assertSame($before + 1, Activity::count());
    }

    /**
     * Deletions are the entries that matter most and the only ones whose
     * subject can no longer name itself — it's gone. The name has to come
     * from what the log captured, or "Hapus Pengguna" reads as an id nobody
     * can look up any more.
     */
    public function test_a_deleted_record_is_still_named_in_the_list(): void
    {
        $this->actingAsSuperAdmin();

        $victim = User::factory()->create(['name' => 'Budi Santoso']);
        $victim->delete();

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('event', 'deleted')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('Pengguna — Budi Santoso', $entry->subjectLabel());
    }

    /**
     * Reading data leaves no trace of its own — nothing is written, so no
     * model event fires. Without this, taking a copy of the sales figures
     * would be the one thing the audit trail couldn't see.
     */
    public function test_exporting_a_report_is_recorded(): void
    {
        $user = $this->actingAsSuperAdmin();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(ListTickets::class)
            ->callAction('export');

        $logged = Activity::query()->where('log_name', 'akses-data')->latest('id')->firstOrFail();

        $this->assertSame('Export Excel', $logged->description);
        $this->assertSame('Laporan Tiket Event', $logged->getProperty('data'));
        $this->assertSame($user->id, $logged->causer_id);
    }

    public function test_opening_evidence_photos_is_recorded(): void
    {
        $user = $this->actingAsSuperAdmin();

        $checklist = ObChecklist::factory()->create();
        $checklist->addMedia(UploadedFile::fake()->image('bukti.jpg'))->toMediaCollection('photos');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(ListObChecklists::class)
            ->mountAction(TestAction::make('viewMedia')->table($checklist));

        $logged = Activity::query()->where('log_name', 'akses-data')->latest('id')->firstOrFail();

        $this->assertSame('Lihat foto', $logged->description);
        $this->assertSame($checklist->id, $logged->subject_id);
        $this->assertSame($user->id, $logged->causer_id);

        Storage::disk('internal')->deleteDirectory((string) $checklist->getFirstMedia('photos')->id);
    }

    /**
     * The one audit category that only appears when somebody is somewhere
     * they shouldn't be. Hooked on the response status rather than on Gate
     * checks: Filament calls the gate constantly just to decide which menu
     * items to draw, and almost all of those legitimately come back false.
     */
    public function test_a_refused_request_is_recorded(): void
    {
        $employee = $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        $before = Activity::count();
        $this->get('/admin')->assertForbidden();

        $denied = Activity::query()->where('log_name', 'ditolak')->latest('id')->firstOrFail();

        $this->assertSame($before + 1, Activity::count());
        $this->assertSame('Akses ditolak', $denied->description);
        $this->assertSame($employee->id, $denied->causer_id);
        $this->assertSame('admin', $denied->getProperty('halaman'));
    }

    /**
     * A guest hitting a panel is redirected to the login page, not refused —
     * logging those would fill the table with people who simply weren't
     * signed in yet.
     */
    public function test_a_guest_being_sent_to_login_is_not_recorded_as_a_refusal(): void
    {
        $before = Activity::count();

        $this->get('/app');

        $this->assertSame($before, Activity::count());
    }

    public function test_a_permitted_request_records_no_refusal(): void
    {
        $this->actingAsSuperAdmin();

        $before = Activity::query()->where('log_name', 'ditolak')->count();
        $this->get('/admin')->assertOk();

        $this->assertSame($before, Activity::query()->where('log_name', 'ditolak')->count());
    }

    /**
     * Colour carries meaning here: red has to mean "look at this" every
     * time, or an admin scanning a long list learns to ignore it.
     */
    public function test_every_log_type_has_a_colour_and_a_label(): void
    {
        foreach (array_keys(ActivityLog::LOG_NAMES) as $logName) {
            $this->assertArrayHasKey($logName, ActivityLog::LOG_COLORS, "[{$logName}] has a label but no colour.");
        }

        $this->assertSame(ActivityLog::LOG_COLORS['izin'], ActivityLog::LOG_COLORS['ditolak']);
        $this->assertSame('danger', ActivityLog::LOG_COLORS['ditolak']);
        $this->assertSame('gray', ActivityLog::LOG_COLORS['data'], 'The bulk of the log must stay visually quiet.');
    }
}
