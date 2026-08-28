<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Concerns\LogsDataAccess;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListActivityLogs extends ListRecords
{
    use LogsDataAccess;

    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Respects whatever filters are currently applied — an auditor
            // usually wants one person or one month, not the whole table.
            ExportAction::make()
                ->label('Export Excel')
                ->after(fn () => $this->logDataAccess('Export Excel', 'Riwayat Aktivitas'))
                ->exports([
                    ExcelExport::make('aktivitas')
                        ->withFilename(fn (): string => 'riwayat-aktivitas-'.now()->format('Y-m-d'))
                        ->withColumns([
                            ExportColumn::make('created_at')->heading('Waktu'),
                            ExportColumn::make('causerUser.name')->heading('Pelaku'),
                            ExportColumn::make('description')->heading('Aktivitas'),
                            ExportColumn::make('log_name')->heading('Jenis'),
                            ExportColumn::make('event')->heading('Aksi'),
                            ExportColumn::make('subject_type')->heading('Jenis Data'),
                            ExportColumn::make('subject_id')->heading('ID Data'),
                        ]),
                ]),
        ];
    }
}
