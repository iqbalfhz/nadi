<?php

namespace App\Filament\Resources\AppCrashReports;

use App\Filament\Resources\AppCrashReports\Pages\ListAppCrashReports;
use App\Filament\Resources\AppCrashReports\Tables\AppCrashReportsTable;
use App\Models\AppCrashReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Failures of the Flutter app, as reported by the handsets themselves.
 *
 * Read-only: the phones write, this reads. See the migration for why the
 * table exists and why rows are grouped rather than appended.
 */
class AppCrashReportResource extends Resource
{
    protected static ?string $model = AppCrashReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sistem');
    }

    public static function getNavigationLabel(): string
    {
        return __('Kegagalan Aplikasi');
    }

    public static function getModelLabel(): string
    {
        return __('Kegagalan Aplikasi');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Kegagalan Aplikasi');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return AppCrashReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppCrashReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
