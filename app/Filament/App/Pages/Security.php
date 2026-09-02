<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ManagesTwoFactor;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Two-factor management for /app.
 *
 * The same page as the admin panel's, so an employee and an administrator
 * secure their account in exactly the same way. Both are thin: everything they
 * do lives in App\Filament\Concerns\ManagesTwoFactor, because Filament
 * discovers pages per panel directory and a shared class cannot serve both.
 */
class Security extends Page
{
    use ManagesTwoFactor;

    /**
     * @var string|array<int, string>
     */
    protected static string|array $routeMiddleware = ['password.confirm'];

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
