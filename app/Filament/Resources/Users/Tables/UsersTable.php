<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Support\Impersonation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['department', 'roles']))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                // Answers "why can't I see the Bazar menu?" by showing the
                // employee's own view instead of reasoning backwards from
                // three stacked layers of roles.
                Action::make('impersonate')
                    ->label('Masuk sebagai')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->color('warning')
                    // Same rule that guards the action itself: a button that
                    // appears and then refuses is worse than no button.
                    ->visible(fn (User $record): bool => Impersonation::isImpersonatable(self::currentUser(), $record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => "Masuk sebagai {$record->name}?")
                    ->modalDescription('Anda akan melihat NADI persis seperti yang dilihat karyawan ini. Perubahan apa pun tercatat atas namanya, disertai penanda bahwa Anda yang mengerjakannya.')
                    ->modalSubmitActionLabel('Masuk sebagai dia')
                    ->action(function (User $record) {
                        Impersonation::start(self::currentUser(), $record);

                        return redirect(Filament::getPanel(
                            Impersonation::landingPanelFor($record) ?? 'app',
                        )->getUrl());
                    }),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== Auth::id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * This table only renders behind Filament's auth middleware, so there is
     * always a user — the cast keeps that fact in one place instead of a null
     * check at every call site.
     */
    private static function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
