<?php

namespace App\Providers\Filament\Concerns;

use Filament\Panel;
use Filament\View\PanelsRenderHook;

/**
 * Shared between both panel providers: accordion nav groups (opening one
 * collapses the others) and a sidebar-collapse toggle placed right after
 * the NADI logo in the topbar, replacing Filament's default one (which sat
 * before the logo). Plain static JS/CSS in public/ (not Vite) so this
 * doesn't depend on every panel having its own Tailwind build configured.
 */
trait HasNadiSidebarCustomizations
{
    protected function withNadiSidebarCustomizations(Panel $panel): Panel
    {
        return $panel
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="'.e(asset('css/nadi-sidebar.css')).'">',
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => <<<'HTML'
                    <button
                        type="button"
                        x-data="{}"
                        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
                        x-bind:aria-expanded="$store.sidebar.isOpen"
                        x-bind:class="{ 'nadi-sidebar-toggle-collapsed': ! $store.sidebar.isOpen }"
                        aria-controls="fi-main-sidebar"
                        aria-label="Buka/tutup sidebar"
                        class="nadi-sidebar-toggle"
                    >
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.5 5L7.5 10L12.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    HTML,
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => '<script src="'.e(asset('js/nadi-sidebar-accordion.js')).'" defer></script>',
            )
            // Developer credit, pinned under the navigation in both panels —
            // visible without competing with the menu, and the one place in
            // the app where it belongs. Icons are inlined rather than pulled
            // from an icon set so this costs no extra request.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.partials.sidebar-credit')->render(),
            );
    }
}
