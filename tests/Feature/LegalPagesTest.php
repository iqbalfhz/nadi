<?php

namespace Tests\Feature;

use App\Support\SocialLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Google will not let an OAuth app leave "Testing" without a reachable privacy
 * policy and terms of use, and an app left in Testing is issued refresh tokens
 * that expire after seven days — which is precisely how the Google Drive
 * backup stopped running without anyone noticing.
 *
 * So these two pages are not decoration: if they stop resolving, or quietly
 * fall behind an auth middleware, the backup integration breaks a week later
 * with no visible cause.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'kebijakan privasi' => ['legal.privacy'],
            'syarat & ketentuan' => ['legal.terms'],
        ];
    }

    #[DataProvider('pages')]
    public function test_a_signed_out_visitor_can_read_it(string $route): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee(SocialLinks::EMAIL);
    }

    #[DataProvider('pages')]
    public function test_each_page_links_to_the_other(string $route): void
    {
        $this->get(route($route))
            ->assertSee(route('legal.privacy'))
            ->assertSee(route('legal.terms'));
    }

    /**
     * Google's reviewers look for a plain statement of what the app does with
     * the Drive permission it asks for. Without it the consent screen can be
     * rejected, which puts the app back in Testing and the token back on a
     * seven-day clock.
     */
    public function test_the_privacy_policy_discloses_what_google_drive_access_is_used_for(): void
    {
        $this->get(route('legal.privacy'))
            ->assertSee('Google Drive')
            ->assertSee('berkas cadangan')
            ->assertSee('dicabut kapan saja', false);
    }

    public function test_the_privacy_policy_covers_what_is_actually_collected(): void
    {
        $response = $this->get(route('legal.privacy'));

        foreach (['Foto', 'alamat IP', '365 hari', 'Telegram'] as $disclosure) {
            $response->assertSee($disclosure, false);
        }
    }
}
