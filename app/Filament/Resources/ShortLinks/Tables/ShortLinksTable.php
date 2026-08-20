<?php

namespace App\Filament\Resources\ShortLinks\Tables;

use App\Models\ShortLink;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Shared between the admin (sees every employee's links) and /app ("Short
 * Link Saya", scoped to the current user) resources — same underlying
 * ShortLink model, just different query scoping and whether the creator
 * column is worth showing.
 */
class ShortLinksTable
{
    public static function configure(Table $table, bool $showCreator = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_url')
                    ->label('Short Link')
                    ->copyable()
                    ->copyMessage('Link disalin!')
                    ->weight('bold'),
                TextColumn::make('target_url')
                    ->label('URL Asli')
                    ->limit(50)
                    ->tooltip(fn (string $state): string => $state)
                    ->url(fn (string $state): string => $state, shouldOpenInNewTab: true),
                ...($showCreator ? [
                    TextColumn::make('creator.name')
                        ->label('Dibuat Oleh')
                        ->searchable(),
                ] : []),
                TextColumn::make('clicks')
                    ->label('Klik')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (ShortLink $record): bool => (Auth::user()?->can('DeleteAny:ShortLink') ?? false) || Auth::id() === $record->created_by),
            ]);
    }
}
