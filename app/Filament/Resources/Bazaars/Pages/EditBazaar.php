<?php

namespace App\Filament\Resources\Bazaars\Pages;

use App\Filament\Resources\Bazaars\BazaarResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditBazaar extends EditRecord
{
    protected static string $resource = BazaarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Removing a Kios/Produk row from the nested Repeater deletes the
     * underlying record — if it already has VendorSale rows attached,
     * restrictOnDelete() rejects that at the database level. Without this,
     * that surfaces as a raw QueryException/500 instead of a message an
     * admin can act on. parent::save() already rolls the transaction back
     * internally before rethrowing, so nothing here needs to touch it again
     * — just report the problem and stop.
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (QueryException $exception) {
            Notification::make()
                ->danger()
                ->title('Tidak bisa menyimpan')
                ->body('Kios atau produk yang dihapus dari form ini sudah memiliki transaksi penjualan, jadi tidak bisa dihapus.')
                ->send();
        }
    }
}
