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
use Filament\Notifications\Notification;
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
                    ->label(__('Nama'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('Departemen'))
                    ->placeholder(__('—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label(__('Roles'))
                    ->badge(),
                IconColumn::make('is_active')
                    ->label(__('Aktif'))
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
                    ->label(__('Masuk sebagai'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->color('warning')
                    // Same rule that guards the action itself: a button that
                    // appears and then refuses is worse than no button.
                    ->visible(fn (User $record): bool => Impersonation::isImpersonatable(self::currentUser(), $record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => "Masuk sebagai {$record->name}?")
                    ->modalDescription(__('Anda akan melihat NADI persis seperti yang dilihat karyawan ini. Perubahan apa pun tercatat atas namanya, disertai penanda bahwa Anda yang mengerjakannya.'))
                    ->modalSubmitActionLabel(__('Masuk sebagai dia'))
                    ->action(function (User $record) {
                        Impersonation::start(self::currentUser(), $record);

                        return redirect(Filament::getPanel(
                            Impersonation::landingPanelFor($record) ?? 'app',
                        )->getUrl());
                    }),
                // For the case deactivation doesn't cover: an employee who is
                // still with the company but has lost their phone. Switching
                // the account off would stop their work too, so this cuts the
                // handset loose and leaves the account alone.
                Action::make('revokeTokens')
                    ->label(__('Keluarkan dari semua perangkat'))
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->tokens()->exists())
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => __('Keluarkan :nama dari semua perangkat?', ['nama' => $record->name]))
                    ->modalDescription(__('Setiap HP yang sedang masuk dengan akun ini akan diminta login ulang. Akunnya sendiri tetap aktif.'))
                    ->action(function (User $record): void {
                        $record->tokens()->delete();

                        Notification::make()
                            ->success()
                            ->title(__('Perangkat dikeluarkan'))
                            ->body(__(':nama harus login ulang di aplikasi.', ['nama' => $record->name]))
                            ->send();
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
