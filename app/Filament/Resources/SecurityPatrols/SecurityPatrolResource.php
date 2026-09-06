<?php

namespace App\Filament\Resources\SecurityPatrols;

use App\Filament\Resources\SecurityPatrols\Pages\EditSecurityPatrol;
use App\Filament\Resources\SecurityPatrols\Pages\ListSecurityPatrols;
use App\Filament\Resources\SecurityPatrols\Pages\ViewSecurityPatrol;
use App\Filament\Resources\SecurityPatrols\Schemas\SecurityPatrolForm;
use App\Filament\Resources\SecurityPatrols\Schemas\SecurityPatrolInfolist;
use App\Filament\Resources\SecurityPatrols\Tables\SecurityPatrolsTable;
use App\Models\SecurityPatrol;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    public static function infolist(Schema $schema): Schema
    {
        return SecurityPatrolInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return SecurityPatrolForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityPatrols::route('/'),
            'view' => ViewSecurityPatrol::route('/{record}'),
            'edit' => EditSecurityPatrol::route('/{record}/edit'),
        ];
    }

    /**
     * A patrol record is the evidence a guard reached a post — the QR code on
     * the sticker is what proves it. One created at a desk is a round that
     * never happened, so there is no create page.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
