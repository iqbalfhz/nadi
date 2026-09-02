<?php

namespace App\Providers\Filament\Concerns;

use App\Support\InterfaceLanguage;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

/**
 * Two buttons in the user menu, directly under Profil and beside Filament's
 * own light/dark switch — the same shape, because it is the same kind of
 * choice: a personal preference about how the interface looks, not a feature.
 *
 * Two options rather than three: the theme switch offers a "follow the system"
 * middle option because an operating system has a theme to follow. There is no
 * equivalent for language worth guessing at, so the choice is simply which one.
 */
trait HasLanguageSwitcher
{
    protected function withLanguageSwitcher(Panel $panel): Panel
    {
        return $panel->renderHook(
            PanelsRenderHook::USER_MENU_PROFILE_AFTER,
            fn (): string => self::languageSwitcher(),
        );
    }

    private static function languageSwitcher(): string
    {
        $current = InterfaceLanguage::current();
        $action = e(route('language.switch'));
        $token = e(csrf_token());

        $buttons = '';

        foreach (InterfaceLanguage::AVAILABLE as $locale => $label) {
            $isCurrent = $locale === $current;

            // Selected state drawn with the same amber the rest of NADI uses
            // for "this one is active".
            $style = $isCurrent
                ? 'background:#fef3c7;color:#b45309;font-weight:600;'
                : 'color:#6b7280;';

            $buttons .= <<<HTML
                <button
                    type="submit"
                    name="locale"
                    value="{$locale}"
                    aria-current="{$isCurrent}"
                    style="flex:1;cursor:pointer;border:0;border-radius:.5rem;padding:.375rem .5rem;font-size:.75rem;line-height:1.2;background:transparent;{$style}"
                >{$label}</button>
                HTML;
        }

        return <<<HTML
            <form method="POST" action="{$action}" style="display:flex;gap:.25rem;margin:0;padding:.25rem .5rem;">
                <input type="hidden" name="_token" value="{$token}">
                {$buttons}
            </form>
            HTML;
    }
}
