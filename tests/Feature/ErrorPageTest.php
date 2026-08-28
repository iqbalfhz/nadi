<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The framework only renders these views when debug mode is off —
        // with it on, Laravel shows its own exception page instead.
        config()->set('app.debug', false);
    }

    public function test_a_missing_page_shows_the_branded_error_page(): void
    {
        $this->get('/tidak-ada-halaman-ini')
            ->assertNotFound()
            ->assertSee('Error 404')
            ->assertSee('Halaman Tidak Ditemukan');
    }

    /**
     * Every status code, not just the handful with their own view: Laravel
     * falls back from errors::{code} to errors::4xx / errors::5xx.
     */
    public function test_every_status_code_lands_on_a_branded_page(): void
    {
        foreach ([400, 402, 403, 404, 405, 408, 413, 419, 422, 429, 500, 502, 503, 504] as $status) {
            Route::get("__probe/{$status}", fn () => throw new HttpException($status))
                ->middleware('web');

            $this->get("__probe/{$status}")
                ->assertStatus($status)
                ->assertSee("Error {$status}")
                ->assertSee('Iqbal Fahrozi');
        }
    }

    public function test_the_back_button_sends_a_guest_to_the_login_page(): void
    {
        $this->get('/tidak-ada-halaman-ini')
            ->assertSee('Masuk ke NADI')
            ->assertSee(route('login'), escape: false);
    }

    public function test_the_back_button_sends_an_admin_back_into_the_admin_panel(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/admin/tidak-ada-halaman-ini')
            ->assertSee('Kembali ke Dashboard Admin')
            ->assertSee(url('/admin'), escape: false);
    }

    public function test_the_back_button_sends_an_employee_back_into_the_app_panel(): void
    {
        // Holding a real /app permission, not a bare account: the button now
        // only offers a panel the person can actually get into.
        $this->actingAsEmployeeWithPermissions('ViewAny:RoomBooking');

        $this->get('/app/tidak-ada-halaman-ini')
            ->assertSee('Kembali ke Dashboard')
            ->assertSee(url('/app'), escape: false);
    }

    /**
     * Nobody standing at the lobby kiosk or in front of the queue TV can log
     * in, so those two screens must never be sent to a login page.
     */
    public function test_the_kiosk_and_display_screens_are_sent_back_to_themselves(): void
    {
        $this->get('/antrian/tidak-ada-halaman-ini')
            ->assertSee('Kembali ke Kiosk Antrian')
            ->assertSee(route('queue.kiosk.take'), escape: false);

        $this->get('/antrian/layar/tidak-ada-halaman-ini')
            ->assertSee('Kembali ke Layar Antrian')
            ->assertSee(route('queue.display'), escape: false);
    }

    public function test_the_social_links_are_shown(): void
    {
        $this->get('/tidak-ada-halaman-ini')
            ->assertSee('https://www.instagram.com/iqbalfhrzi_/', escape: false)
            ->assertSee('https://github.com/iqbalfhz?tab=repositories', escape: false);
    }

    /**
     * Laravel registers its own error views under the same errors:: namespace
     * as resources/views/errors, and a code it ships a view for wins before
     * the 4xx/5xx fallback is ever consulted. So every vendor view needs a
     * local counterpart — 402 was missed exactly this way, and only showed up
     * because the loop above happened to include it.
     */
    public function test_every_framework_error_view_has_a_branded_override(): void
    {
        $vendorViews = glob(base_path('vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/views/*.blade.php')) ?: [];

        foreach ($vendorViews as $vendorView) {
            $name = basename($vendorView, '.blade.php');

            if (! is_numeric($name)) {
                continue;
            }

            $this->assertFileExists(
                resource_path("views/errors/{$name}.blade.php"),
                "Laravel ships its own errors::{$name} view, which takes priority over the 4xx/5xx fallback — add resources/views/errors/{$name}.blade.php or that code renders unbranded.",
            );
        }
    }

    public function test_the_social_links_also_appear_in_both_panel_sidebars(): void
    {
        $this->actingAsSuperAdmin();

        foreach (['/admin', '/app'] as $panel) {
            $this->get($panel)
                ->assertOk()
                ->assertSee('https://www.instagram.com/iqbalfhrzi_/', escape: false)
                ->assertSee('https://github.com/iqbalfhz?tab=repositories', escape: false)
                ->assertSee('Dibuat oleh Iqbal Fahrozi');
        }
    }

    /**
     * The bug this replaces: a 403 at /admin offered "Kembali ke Dashboard
     * Admin", which sent the employee straight back to the page that had
     * just refused them — an endless loop with no way out of it.
     */
    public function test_an_employee_refused_at_admin_is_sent_to_their_own_panel(): void
    {
        $this->actingAsEmployeeWithPermissions(['ViewAny:RoomBooking', 'Create:RoomBooking']);

        $this->get('/admin')
            ->assertForbidden()
            ->assertSee('Kembali ke Dashboard')
            ->assertDontSee('Kembali ke Dashboard Admin')
            ->assertSee(url('/app'), escape: false);
    }

    public function test_an_admin_refused_inside_admin_is_still_sent_back_there(): void
    {
        // They can enter the panel, just not that one resource — so the
        // panel really is where they should go back to.
        $this->actingAsEmployeeWithPermissions('ViewAny:Document');

        $this->get('/admin/users')
            ->assertForbidden()
            ->assertSee('Kembali ke Dashboard Admin')
            ->assertSee(url('/admin'), escape: false);
    }

    /**
     * An account whose role holds nothing at all can reach neither panel, so
     * there is no page to offer — only signing out, which Fortify accepts
     * as a POST.
     */
    public function test_an_account_with_no_access_at_all_is_offered_a_way_out(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/admin');

        $response->assertForbidden()
            ->assertSee('Keluar')
            ->assertSee(route('logout'), escape: false)
            ->assertSee('method="POST"', escape: false);

        // ...and it must never point at a page that would refuse them again.
        $response->assertDontSee('Kembali ke Dashboard');
    }
}
