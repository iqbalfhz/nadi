<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * The two languages NADI's interface is offered in, and the rules around them.
 *
 * Indonesian is the source language: strings are written in Indonesian in the
 * code, so `lang/id.json` needs no entry for them and English lives in
 * `lang/en.json`. (The reverse holds for the few screens inherited from the
 * Livewire starter kit, whose sources are English — those are translated *into*
 * Indonesian by `lang/id.json`. Each dictionary only loads for its own locale,
 * so the two directions never collide.)
 */
final class InterfaceLanguage
{
    /**
     * Ordered as they appear in the switcher: Indonesian on the left because
     * it is the office's own language and the overwhelming default.
     *
     * @var array<string, string>
     */
    public const AVAILABLE = [
        'id' => 'Indonesia',
        'en' => 'English',
    ];

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::AVAILABLE);
    }

    /**
     * What the given user should see. A null preference means "follow the
     * application", which is what lets APP_LOCALE still move anyone who never
     * chose for themselves.
     */
    public static function for(?User $user): string
    {
        $preference = $user?->locale;

        if (is_string($preference) && self::isSupported($preference)) {
            return $preference;
        }

        return self::fallback();
    }

    public static function fallback(): string
    {
        $configured = (string) config('app.locale');

        return self::isSupported($configured) ? $configured : 'id';
    }

    public static function current(): string
    {
        $locale = App::getLocale();

        return self::isSupported($locale) ? $locale : self::fallback();
    }

    /**
     * Records the choice against the account so it follows the person to every
     * device, rather than living in one browser's session.
     */
    public static function remember(User $user, string $locale): void
    {
        if (! self::isSupported($locale)) {
            return;
        }

        $user->forceFill(['locale' => $locale])->save();

        App::setLocale($locale);
    }

    public static function apply(): void
    {
        $user = Auth::user();

        App::setLocale(self::for($user instanceof User ? $user : null));
    }
}
