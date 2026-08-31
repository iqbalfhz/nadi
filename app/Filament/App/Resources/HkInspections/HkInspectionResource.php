<?php

namespace App\Filament\App\Resources\HkInspections;

use App\Filament\App\Resources\HkInspections\Pages\CreateHkInspection;
use App\Filament\App\Resources\HkInspections\Pages\ListHkInspections;
use App\Filament\App\Resources\HkInspections\Schemas\HkInspectionForm;
use App\Filament\App\Resources\HkInspections\Tables\HkInspectionsTable;
use App\Models\HkInspection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * @extends resource<HkInspection>
 */
class HkInspectionResource extends Resource
{
    protected static ?string $model = HkInspection::class;

    protected static ?string $modelLabel = 'Checklist HK';

    protected static ?string $pluralModelLabel = 'Laporan Saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'HK';

    protected static ?string $navigationLabel = 'Laporan Saya';

    public static function form(Schema $schema): Schema
    {
        return HkInspectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HkInspectionsTable::configure($table);
    }

    /**
     * Scoped to the signed-in supervisor, like every other self-service
     * resource in this panel. The company-wide view lives in /admin.
     *
     * @return Builder<HkInspection>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'area'])
            ->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHkInspections::route('/'),
            'create' => CreateHkInspection::route('/create'),
        ];
    }
}
