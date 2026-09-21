<?php

namespace App\Filament\Resources\SecurityPatrols\Schemas;

use App\Enums\PatrolReviewFlag;
use App\Filament\Schemas\FieldReportEntries;
use App\Models\SecurityPatrol;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SecurityPatrolInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Kunjungan'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('checkpoint.name')
                            ->label(__('Titik Patroli')),
                        TextEntry::make('user.name')
                            ->label(__('Petugas')),
                        TextEntry::make('incident_report')
                            ->label(__('Laporan Kejadian'))
                            ->placeholder(__('Tidak ada kejadian dilaporkan'))
                            ->color(fn (?string $state): string => $state ? 'danger' : 'gray')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Foto'))
                    ->icon(Heroicon::OutlinedCamera)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('photos')
                            ->hiddenLabel()
                            ->collection('photos')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Waktu'))
                    ->description(__('Jarak antara keduanya adalah lamanya laporan menunggu sinyal di HP petugas. Tangga darurat dan area parkir justru tempat sinyal paling buruk.'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->schema([
                        FieldReportEntries::reportedAt()->label(__('Waktu Kunjungan')),
                        FieldReportEntries::receivedAt(),
                        FieldReportEntries::claimedAt(),
                    ]),
                // Hidden entirely on an ordinary patrol. A panel that shows
                // an empty "nothing suspicious" box on every record teaches
                // people to stop reading the box.
                Section::make(__('Perlu Ditinjau'))
                    ->description(__('Semua penanda di bawah punya penjelasan yang sah. Tidak ada laporan yang ditolak karenanya — ini hanya penunjuk ke mana harus melihat.'))
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->columnSpanFull()
                    ->visible(fn (SecurityPatrol $record): bool => filled($record->review_flags))
                    ->schema([
                        TextEntry::make('review_flags')
                            ->hiddenLabel()
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => PatrolReviewFlag::tryFrom($state)?->label() ?? $state)
                            ->color(fn (string $state): string => PatrolReviewFlag::tryFrom($state)?->color() ?? 'gray')
                            ->columnSpanFull(),
                        // The instruction, not just the label. A supervisor
                        // told "impossible travel" and nothing else will
                        // reach for the wrong conclusion.
                        TextEntry::make('review_guidance')
                            ->label(__('Yang sebaiknya dilakukan'))
                            ->state(fn (SecurityPatrol $record): string => collect($record->review_flags ?? [])
                                ->map(fn (string $flag): ?string => PatrolReviewFlag::tryFrom($flag)?->guidance())
                                ->filter()
                                ->implode(' '))
                            ->columnSpanFull(),
                        TextEntry::make('reviewed_at')
                            ->label(__('Sudah Ditinjau'))
                            ->dateTime('d M Y H:i')
                            ->placeholder(__('Belum ditinjau'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
