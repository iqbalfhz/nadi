<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_redirects_into_the_dashboard_bounce_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect('/dashboard');
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));

        $response = $this->get('/register');

        $response->assertNotFound();
    }
}
