<?php

namespace App\Filament\Resources\HkInspections;

use App\Filament\Resources\HkInspections\Pages\ListHkInspections;
use App\Filament\Resources\HkInspections\Tables\HkInspectionsTable;
use App\Models\HkInspection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<HkInspection>
 */
class HkInspectionResource extends Resource
{
    protected static ?string $model = HkInspection::class;

    public static function getModelLabel(): string
    {
        return __('Checklist HK');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Laporan HK');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('HK');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Laporan');
    }

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return HkInspectionsTable::configure($table);
    }

    /**
     * @return Builder<HkInspection>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'area', 'user'])
            ->withCount('media');
    }

    /**
     * Index only. A filed inspection is a record of what a supervisor found at
     * a moment in time, so there is deliberately no edit or delete page —
     * same stance as the OB and Security checklists.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListHkInspections::route('/'),
        ];
    }
}
