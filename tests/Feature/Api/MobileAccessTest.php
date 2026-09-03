<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who the mobile app lets in, and how someone is put back out.
 *
 * Both halves are easy to get subtly wrong in a way nothing complains about:
 * a gate that admits too much looks identical to one that works, and a
 * revocation that misses the phone looks identical to one that doesn't.
 */
class MobileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_field_worker_is_let_in(): void
    {
        $this->actingAsMobileUser('Create:ObChecklist');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.modules', ['ob']);
    }

    /**
     * A cashier holds a real /app permission and has every right to sign in
     * to the web. The phone carries the field modules only, so their token
     * must not open it.
     */
    public function test_a_desk_bound_employee_is_refused(): void
    {
        $this->actingAsMobileUser(['ViewAny:Ticket', 'View:SellTicket']);

        $this->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_each_job_sees_only_its_own_modules(): void
    {
        $this->actingAsMobileUser(['Create:ObChecklist', 'View:MessengerTasks']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.modules', ['ob', 'messenger']);
    }

    /**
     * UserObserver drops the browser session when an account is switched
     * off. Before the API existed that was the whole story; now the phone is
     * the device that walks out of the building, and a bearer token would
     * otherwise outlive every other way of shutting the account out.
     */
    public function test_deactivating_an_account_revokes_its_tokens(): void
    {
        $worker = $this->actingAsMobileUser('Create:ObChecklist');
        $token = $worker->createToken('Redmi Note 12', ['mobile'])->plainTextToken;

        $this->forgetAuthenticatedUser();
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $worker->update(['is_active' => false]);

        $this->forgetAuthenticatedUser();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();

        $this->assertSame(0, $worker->fresh()->tokens()->count());
    }

    /**
     * Deactivation is not the only case. An employee who is still with the
     * company but has lost their phone needs the same button, which is why
     * /admin has one — this is the behaviour it relies on.
     */
    public function test_revoking_tokens_leaves_the_account_usable(): void
    {
        $worker = $this->actingAsMobileUser('Create:ObChecklist');
        $token = $worker->createToken('HP lama', ['mobile'])->plainTextToken;

        $worker->tokens()->delete();

        $this->forgetAuthenticatedUser();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();

        // The account itself is untouched — they can sign in again on a new
        // handset.
        $this->assertTrue($worker->fresh()->is_active);
        $this->assertTrue($worker->fresh()->canUseMobileApp());
    }
}
