<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\PageRegistration;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Resource forms render in a 2-column grid and *every* Filament component
 * defaults to spanning a single column — layout containers included. A Section
 * or Repeater that forgets columnSpanFull() therefore renders in the left half
 * of the page with the entire right half blank, which is exactly how the Bazar
 * form shipped before this test existed.
 *
 * This walks every create form we own in both panels rather than checking the
 * one form that was reported, so a new resource can't quietly reintroduce it.
 */
class FormLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Types that must never be squeezed into part of a multi-column row:
     * containers (their own contents should get the space) and fields whose
     * controls — a dropzone, a repeater's nested grid, a multi-line box —
     * become unusable at half width.
     *
     * @var array<int, class-string>
     */
    private const MUST_SPAN_FULL_WIDTH = [
        Section::class,
        Fieldset::class,
        Grid::class,
        Repeater::class,
        SpatieMediaLibraryFileUpload::class,
        Textarea::class,
    ];

    public function test_no_create_form_leaves_part_of_a_row_blank(): void
    {
        $this->actingAsSuperAdmin();

        $checked = 0;

        foreach (['admin', 'app'] as $panelId) {
            Filament::setCurrentPanel(Filament::getPanel($panelId));

            foreach ($this->createPagesOf($panelId) as $page) {
                $schema = Livewire::test($page)->instance()->getSchema('form');

                $this->assertInstanceOf(Schema::class, $schema);

                $this->assertWideComponentsSpanFullWidth($schema, $page);

                $checked++;
            }
        }

        // Guards the guard: a broken discovery loop would silently pass.
        $this->assertGreaterThan(10, $checked, 'Expected to inspect every resource create form.');
    }

    private function assertWideComponentsSpanFullWidth(Schema $schema, string $page): void
    {
        $columns = $this->columnCountOf($schema);

        foreach ($schema->getComponents() as $component) {
            // In a single-column container everything is full width already,
            // so an explicit span there is a style choice, not a bug.
            if ($columns > 1) {
                foreach (self::MUST_SPAN_FULL_WIDTH as $type) {
                    if (! $component instanceof $type) {
                        continue;
                    }

                    $this->assertSame(
                        'full',
                        $component->getColumnSpan()['default'] ?? null,
                        class_basename($type)." on [{$page}] needs ->columnSpanFull(): its container has {$columns} columns, so it renders in part of the row and leaves the rest blank.",
                    );
                }
            }

            // A Section's own children and a Repeater's item fields run their
            // own grids, so the same rule applies one level down.
            foreach ($component->getChildSchemas() as $childSchema) {
                $this->assertWideComponentsSpanFullWidth($childSchema, $page);
            }
        }
    }

    private function columnCountOf(Schema $schema): int
    {
        $columns = $schema->getColumns();

        if (is_int($columns)) {
            return $columns;
        }

        $counts = array_filter((array) $columns, 'is_int');

        return $counts === [] ? 1 : max($counts);
    }

    /**
     * Only resources this application owns — a packaged resource's layout
     * (Filament Shield's Roles form) isn't ours to change.
     *
     * @return array<int, class-string<CreateRecord>>
     */
    private function createPagesOf(string $panelId): array
    {
        $pages = [];

        foreach (Filament::getPanel($panelId)->getResources() as $resource) {
            if (! str_starts_with((string) $resource, 'App')) {
                continue;
            }

            foreach ($resource::getPages() as $key => $registration) {
                if ($key !== 'create' || ! $registration instanceof PageRegistration) {
                    continue;
                }

                $page = $registration->getPage();

                if (is_subclass_of($page, CreateRecord::class)) {
                    $pages[] = $page;
                }
            }
        }

        return $pages;
    }
}
