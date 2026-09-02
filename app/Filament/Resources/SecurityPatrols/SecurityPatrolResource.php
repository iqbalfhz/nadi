<?php

namespace App\Filament\Resources\SecurityPatrols;

use App\Filament\Resources\SecurityPatrols\Pages\ListSecurityPatrols;
use App\Filament\Resources\SecurityPatrols\Tables\SecurityPatrolsTable;
use App\Models\SecurityPatrol;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<SecurityPatrol>
 */
class SecurityPatrolResource extends Resource
{
    protected static ?string $model = SecurityPatrol::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Patroli');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Security');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Patroli');
    }

    public static function getModelLabel(): string
    {
        return __('Patroli Security');
    }

    /**
     * @return Builder<SecurityPatrol>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['checkpoint', 'user'])
            ->withCount('media');
    }

    public static function table(Table $table): Table
    {
        return SecurityPatrolsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityPatrols::route('/'),
        ];
    }
}
