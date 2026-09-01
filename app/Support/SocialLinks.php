<?php

namespace App\Support;

/**
 * The developer's public profiles, in one place so the error pages and the
 * panel sidebar credit can never drift apart.
 */
final class SocialLinks
{
    public const AUTHOR = 'Iqbal Fahrozi';

    /**
     * Shown on the public privacy and terms pages, and registered as the
     * support contact on the Google OAuth consent screen \u2014 the two have to
     * match for the app to stay publishable.
     */
    public const EMAIL = 'iqbalfahrozi284@gmail.com';

    public const INSTAGRAM = 'https://www.instagram.com/iqbalfhrzi_/';

    public const GITHUB = 'https://github.com/iqbalfhz?tab=repositories';

    /**
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public static function all(): array
    {
        return [
            [
                'label' => 'Instagram',
                'url' => self::INSTAGRAM,
                'icon' => 'instagram',
            ],
            [
                'label' => 'GitHub',
                'url' => self::GITHUB,
                'icon' => 'github',
            ],
        ];
    }
}
