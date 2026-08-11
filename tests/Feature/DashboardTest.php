<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_to_the_app_panel(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect('/app');
    }

    public function test_super_admins_are_redirected_to_the_admin_panel(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('dashboard'));

        $response->assertRedirect('/admin');
    }
}
