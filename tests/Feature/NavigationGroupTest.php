<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Resources\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A panel only orders and gives icons to the groups it was told about. Anything
 * a resource invents beyond that list still appears — but in an arbitrary
 * position and with no icon beside it, which is how the HK menu shipped:
 * wedged between Penomoran Dokumen and Booking Room, the one bare label in a
 * sidebar of icons.
 *
 * Nothing fails when this happens, so only a test catches it.
 */
class NavigationGroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{string}>
     */
    public static function panels(): array
    {
        return [['admin'], ['app']];
    }

    /**
     * Deliberately checked in one language only.
     *
     * A version of this that re-ran under `en` was written and then removed: a
     * missing translation makes __() return the source string on *both* sides,
     * so the two still match and the test can never fail independently. What
     * actually protects English is
     * InterfaceLanguageTest::test_every_translatable_string_in_the_code_has_an_english_counterpart,
     * which fails when an entry is missing from lang/en.json.
     */
    #[DataProvider('panels')]
    public function test_every_group_a_resource_uses_is_registered_on_its_panel(string $panelId): void
    {
        $panel = Filament::getPanel($panelId);

        $registered = array_values(array_filter(array_map(
            fn (NavigationGroup $group): ?string => $group->getLabel(),
            $panel->getNavigationGroups(),
        )));

        $this->assertNotEmpty($registered, "Panel [{$panelId}] registers no navigation groups at all.");

        $checked = 0;

        foreach ($panel->getResources() as $resource) {
            // A packaged resource places itself; only ours are in scope.
            if (! str_starts_with((string) $resource, 'App')) {
                continue;
            }

            $group = self::groupOf($resource);

            if ($group === null) {
                continue;
            }

            $this->assertContains(
                $group,
                $registered,
                "[{$resource}] sits in navigation group \"{$group}\", which panel [{$panelId}] never registers — "
                    .'so it lands in an unpredictable spot in the sidebar and shows without an icon. '
                    ."Add NavigationGroup::make('{$group}')->icon(...) to the panel provider.",
            );

            $checked++;
        }

        $this->assertGreaterThan(3, $checked, "Expected to inspect several grouped resources on [{$panelId}].");
    }

    /**
     * @param  class-string<resource>  $resource
     */
    /**
     * Read through the method, not the static property it replaced: the group
     * name is translated now, and a property initialiser cannot call __().
     */
    private static function groupOf(string $resource): ?string
    {
        $value = $resource::getNavigationGroup();

        return is_string($value) ? $value : null;
    }
}
