<?php

namespace App\Filament\Resources\SecurityCheckpoints;

use App\Filament\Resources\SecurityCheckpoints\Pages\CreateSecurityCheckpoint;
use App\Filament\Resources\SecurityCheckpoints\Pages\EditSecurityCheckpoint;
use App\Filament\Resources\SecurityCheckpoints\Pages\ListSecurityCheckpoints;
use App\Filament\Resources\SecurityCheckpoints\Schemas\SecurityCheckpointForm;
use App\Filament\Resources\SecurityCheckpoints\Tables\SecurityCheckpointsTable;
use App\Models\SecurityCheckpoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SecurityCheckpointResource extends Resource
{
    protected static ?string $model = SecurityCheckpoint::class;

    public static function getPluralModelLabel(): string
    {
        return __('Titik Patroli');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Security');
    }

    public static function getNavigationLabel(): string
    {
        return __('Titik Patroli');
    }

    public static function getModelLabel(): string
    {
        return __('Titik Patroli');
    }

    public static function form(Schema $schema): Schema
    {
        return SecurityCheckpointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecurityCheckpointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityCheckpoints::route('/'),
            'create' => CreateSecurityCheckpoint::route('/create'),
            'edit' => EditSecurityCheckpoint::route('/{record}/edit'),
        ];
    }
}
