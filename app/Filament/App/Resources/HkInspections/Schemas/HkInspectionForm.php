<?php

namespace App\Filament\App\Resources\HkInspections\Schemas;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Models\HkArea;
use App\Models\HkCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The supervisor's inspection form, filled on a phone while standing at the
 * point being checked.
 *
 * Two fields appear conditionally, and they are driven by different things on
 * purpose:
 *
 * - "Lantai" follows the *category*, because whether a floor is already part
 *   of a point's name is a property of how that category names its points.
 * - "Tindak Lanjut" follows the *condition*, because needing a follow-up is a
 *   property of the finding, not of where it was found. A clean Public Area
 *   needs none; a dirty toilet does.
 */
class HkInspectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Laporan Pemeriksaan')
                    ->description('Isi setelah selesai memeriksa satu titik.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    // Single column throughout: this is filled one-handed on a
                    // phone at the location, and a camera dropzone squeezed
                    // into half a row is unusable there. Same reasoning as
                    // ObChecklistForm.
                    ->schema([
                        Select::make('hk_category_id')
                            ->label('Kategori')
                            ->options(fn (): array => HkCategory::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->live()
                            // Points belong to exactly one category, so a
                            // point chosen under the old category would be
                            // wrong the moment the category changes.
                            ->afterStateUpdated(fn (Set $set) => $set('hk_area_id', null))
                            ->columnSpanFull(),
                        Select::make('hk_area_id')
                            ->label('Titik')
                            ->options(fn (Get $get): array => HkArea::query()
                                ->where('is_active', true)
                                ->where('hk_category_id', $get('hk_category_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('hk_category_id')))
                            ->placeholder(fn (Get $get): string => blank($get('hk_category_id'))
                                ? 'Pilih kategori dulu'
                                : 'Pilih titik')
                            ->columnSpanFull(),
                        TextInput::make('floor')
                            ->label('Lantai')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::categoryRequiresFloor($get('hk_category_id')))
                            ->required(fn (Get $get): bool => self::categoryRequiresFloor($get('hk_category_id')))
                            ->columnSpanFull(),
                        TextInput::make('staff_name')
                            ->label('Petugas')
                            ->helperText('Nama staf HK yang bertugas di titik ini saat diperiksa.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('shift')
                            ->label('Shift')
                            ->options(HkShift::options())
                            ->required()
                            ->columnSpanFull(),
                        Select::make('condition')
                            ->label('Kondisi')
                            ->options(HkCondition::options())
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->label('Foto')
                            ->helperText('Ambil langsung dari kamera atau pilih dari galeri. Bisa lebih dari satu.')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->maxSize(10240)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Keterangan')
                            ->helperText('Opsional — jelaskan temuannya kalau ada.')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('follow_up')
                            ->label('Tindak Lanjut')
                            ->helperText('Apa yang sudah atau akan dilakukan atas temuan ini.')
                            ->maxLength(1000)
                            ->rows(3)
                            // Both visible and required together: reporting a
                            // problem and walking away without saying what was
                            // done about it is the gap this closes.
                            ->visible(fn (Get $get): bool => self::conditionNeedsFollowUp($get('condition')))
                            ->required(fn (Get $get): bool => self::conditionNeedsFollowUp($get('condition')))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function categoryRequiresFloor(mixed $categoryId): bool
    {
        if (blank($categoryId)) {
            return false;
        }

        return (bool) HkCategory::query()->whereKey($categoryId)->value('requires_floor');
    }

    private static function conditionNeedsFollowUp(mixed $condition): bool
    {
        if (blank($condition)) {
            return false;
        }

        // The select hands back the raw string; the enum owns the rule.
        return HkCondition::tryFrom(is_string($condition) ? $condition : '')?->needsFollowUp() ?? false;
    }
}
