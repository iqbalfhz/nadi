<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * An unexpected failure behind the API, as the phone and the log each see it.
 */
class ServerErrorTest extends TestCase
{
    public function test_a_server_error_is_logged_once_and_answered_in_plain_words(): void
    {
        Exceptions::fake();

        Route::get('api/v1/__probe/boom', fn () => throw new RuntimeException('boom'));

        $this->getJson('/api/v1/__probe/boom')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Terjadi kesalahan di server. Coba lagi nanti.']);

        // Laravel reports before it renders, so the render callback must not report again.
        Exceptions::assertReportedCount(1);
    }
}
