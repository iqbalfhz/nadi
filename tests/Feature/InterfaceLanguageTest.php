<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards the interface against drifting back into English or into machine
 * output.
 *
 * Every one of these checks corresponds to something that was actually broken
 * on screen before this test existed: the app ran on APP_LOCALE=en so Laravel
 * answered a mistyped form in English, no lang/id files existed at all, and
 * resources without an explicit label were named by Filament's English
 * pluraliser — which produced "Dokumens", "PTS", "Pengirimen" and "Patroli
 * Securities" in page titles, breadcrumbs and delete buttons.
 */
class InterfaceLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_speaks_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
    }

    /**
     * Left to itself Filament derives a resource's name from its class and
     * pluralises with English rules. Asserting the property is *redeclared on
     * the resource itself* — not merely non-empty, which the inherited null
     * would also satisfy once a label is derived — is what stops a new
     * resource shipping as "Dokumens".
     */
    public function test_every_resource_names_itself_explicitly(): void
    {
        $checked = 0;

        foreach (['admin', 'app'] as $panelId) {
            foreach (Filament::getPanel($panelId)->getResources() as $resource) {
                // A packaged resource's naming (Filament Shield's Roles)
                // isn't ours to set.
                if (! str_starts_with((string) $resource, 'App')) {
                    continue;
                }

                foreach (['modelLabel', 'pluralModelLabel'] as $property) {
                    $declaration = (new ReflectionClass($resource))->getProperty($property);

                    $this->assertSame(
                        $resource,
                        $declaration->getDeclaringClass()->getName(),
                        "[{$resource}] does not declare \${$property}, so Filament will invent one by pluralising the class name with English rules.",
                    );

                    $this->assertNotEmpty(
                        $declaration->getValue(),
                        "[{$resource}] declares \${$property} but leaves it empty.",
                    );
                }

                $checked++;
            }
        }

        // Guards the guard: a broken discovery loop would silently pass.
        $this->assertGreaterThan(20, $checked, 'Expected to inspect every resource in both panels.');
    }

    /**
     * Laravel ships English validation messages only. Without lang/id these
     * come back as "The name field is required." — mid-form, to someone who
     * only wanted to know which box they missed.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $rules
     */
    #[DataProvider('validationMessages')]
    public function test_validation_speaks_indonesian(array $data, array $rules, string $expected): void
    {
        $this->assertSame(
            $expected,
            Validator::make($data, $rules)->errors()->first(),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, string>, string}>
     */
    public static function validationMessages(): array
    {
        return [
            'required' => [[], ['name' => 'required'], 'Nama wajib diisi.'],
            'email' => [['email' => 'bukan email'], ['email' => 'email'], 'Email harus berupa alamat email yang valid.'],
            'numeric' => [['code' => 'abc'], ['code' => 'numeric'], 'Kode harus berupa angka.'],
            'confirmed' => [['password' => 'a'], ['password' => 'confirmed'], 'Konfirmasi Password tidak cocok.'],
        ];
    }

    /**
     * Strings that legitimately reach the screen untranslated: either already
     * written in Indonesian inside the view, or a word spelled the same in both
     * languages.
     *
     * Anything else must have an entry in lang/id.json. Adding to this list is
     * a deliberate act — that is the point.
     *
     * @var array<int, string>
     */
    private const PASSES_THROUGH_UNTRANSLATED = [
        'Atau konfirmasi dengan password',
        'Dashboard',
        'Email',
        'Ingat saya',
        'Ini area aman — konfirmasi password Anda dulu untuk melanjutkan.',
        'Konfirmasi',
        'Konfirmasi Password',
        'Konfirmasi dengan passkey',
        'Lupa password?',
        'Masuk',
        'Masuk dengan akun NADI Anda untuk melanjutkan.',
        'Mengonfirmasi...',
        'Password',
        'Platform',
        'Repository',
        'Selamat datang kembali',
    ];

    /**
     * Every translatable string in every view, with no length limit.
     *
     * The audit script this replaces capped strings at 90 characters, so seven
     * English paragraphs on the two-factor screen were never even looked at —
     * including the one an employee reads while deciding whether to turn 2FA
     * on. A cap is exactly the kind of quiet blind spot a test should not have.
     */
    public function test_no_view_shows_an_untranslated_string(): void
    {
        $dictionary = json_decode((string) file_get_contents(lang_path('id.json')), true);

        $this->assertIsArray($dictionary);

        $untranslated = [];
        $checked = 0;

        foreach ($this->viewFiles() as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match_all("/__\('([^']{2,400})'\)/", $source, $matches)) {
                continue;
            }

            foreach ($matches[1] as $string) {
                $checked++;

                if (isset($dictionary[$string]) || in_array($string, self::PASSES_THROUGH_UNTRANSLATED, true)) {
                    continue;
                }

                $untranslated[$string] = basename($file);
            }
        }

        $this->assertGreaterThan(50, $checked, 'Expected to inspect every __() string in resources/views.');

        $this->assertSame(
            [],
            $untranslated,
            'These reach the screen in English. Add each to lang/id.json, or to PASSES_THROUGH_UNTRANSLATED if it is the same word in Indonesian.',
        );
    }

    /**
     * @return array<int, string>
     */
    private function viewFiles(): array
    {
        $files = [];

        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($directory as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * The login, password-reset and account-settings screens come from the
     * Livewire starter kit and are written against English source strings, so
     * lang/id.json is the only thing standing between an employee and a
     * sign-in page that reads "Log in".
     */
    public function test_the_starter_kit_screens_are_translated(): void
    {
        foreach ([
            'Log in' => 'Masuk',
            'Log out' => 'Keluar',
            'Settings' => 'Pengaturan',
            'Forgot password' => 'Lupa Password',
            'Two-factor authentication' => 'Autentikasi dua langkah',
            'Please enter your new password below' => 'Masukkan password baru Anda di bawah ini',
        ] as $source => $expected) {
            $this->assertSame($expected, __($source), "[{$source}] is missing from lang/id.json.");
        }
    }
}
