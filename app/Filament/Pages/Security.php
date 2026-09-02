<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ManagesTwoFactor;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Two-factor management for /admin.
 *
 * This replaces a Livewire page from the starter kit that carried its own
 * application shell — a sidebar reading "Platform / Repository /
 * Documentation", none of which belongs to NADI. Opening your security
 * settings meant leaving the product and landing somewhere that looked like a
 * different application entirely.
 *
 * Deliberately not gated by Shield: every account manages its own security, the
 * same way every account manages its own profile. There is no permission here
 * worth granting or withholding.
 */
class Security extends Page
{
    use ManagesTwoFactor;

    /**
     * Fortify's own confirmation screen, reached when this page is opened.
     * Reading or changing two-factor settings should cost a password even when
     * a session is already open — an unlocked laptop is the exact case this
     * defends against.
     *
     * @var string|array<int, string>
     */
    protected static string|array $routeMiddleware = ['password.confirm'];

    /**
     * Reached from the user menu, next to Profil, rather than the sidebar:
     * this is a property of the account, not a module of the business.
     */
    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return __('Keamanan Akun');
    }

    protected string $view = 'filament.pages.security';

    protected function getHeaderActions(): array
    {
        return [
            $this->enableAction(),
            $this->recoveryCodesAction(),
            $this->regenerateRecoveryCodesAction(),
            $this->disableAction(),
        ];
    }
}
