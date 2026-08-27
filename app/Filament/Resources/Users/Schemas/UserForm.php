<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Karyawan')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Select::make('department_id')
                            ->label('Departemen')
                            ->options(fn () => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Kosongkan jika tidak ingin mengubah password.'
                                : null),
                    ]),

                Section::make('Akses')
                    ->description('Role menentukan menu apa saja yang bisa dibuka user ini di /admin dan /app.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record?->id === Auth::id())
                            ->helperText(fn (?User $record): string => $record?->id === Auth::id()
                                ? 'Tidak bisa menonaktifkan akun sendiri.'
                                : 'User yang dinonaktifkan tidak bisa login sama sekali.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
