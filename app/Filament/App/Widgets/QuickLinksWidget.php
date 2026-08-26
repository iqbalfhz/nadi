<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\GenerateBarcode;
use App\Filament\App\Pages\MessengerTasks;
use App\Filament\App\Pages\QueueOperator;
use App\Filament\App\Pages\SecurityScan;
use App\Filament\App\Pages\SellTicket;
use App\Filament\App\Pages\SellVendorProduct;
use App\Filament\App\Resources\MessengerDeliveries\MessengerDeliveryResource;
use App\Filament\App\Resources\ObChecklists\ObChecklistResource;
use App\Filament\App\Resources\RoomBookings\RoomBookingResource;
use App\Filament\App\Resources\ShortLinks\ShortLinkResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * The /app dashboard's landing content — a permission-filtered grid of
 * shortcuts to every self-service action page, so an employee lands
 * somewhere useful instead of just the Booking Room calendar (which used to
 * be the dashboard's only content, misleading for anyone who isn't there to
 * book a room). Each link is gated by the same permission that gates its
 * destination page, so this never shows a card that would 403 on click.
 */
class QuickLinksWidget extends Widget
{
    protected string $view = 'filament.app.widgets.quick-links-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    /**
     * @return array<int, array{label: string, icon: string|Heroicon, url: string}>
     */
    public function getLinks(): array
    {
        $user = Auth::user();

        return collect([
            ['label' => 'Booking Room', 'icon' => Heroicon::OutlinedCalendarDays, 'permission' => 'ViewAny:RoomBooking', 'url' => fn () => RoomBookingResource::getUrl('index')],
            ['label' => 'Checklist OB', 'icon' => Heroicon::OutlinedClipboardDocumentCheck, 'permission' => 'Create:ObChecklist', 'url' => fn () => ObChecklistResource::getUrl('create')],
            ['label' => 'Kirim Dokumen', 'icon' => Heroicon::OutlinedTruck, 'permission' => 'Create:MessengerDelivery', 'url' => fn () => MessengerDeliveryResource::getUrl('create')],
            ['label' => 'Tugas Kurir', 'icon' => Heroicon::OutlinedTruck, 'permission' => 'View:MessengerTasks', 'url' => fn () => MessengerTasks::getUrl()],
            ['label' => 'Operator Antrian', 'icon' => Heroicon::OutlinedSpeakerWave, 'permission' => 'View:QueueOperator', 'url' => fn () => QueueOperator::getUrl()],
            ['label' => 'Scan Patroli Security', 'icon' => Heroicon::OutlinedShieldCheck, 'permission' => 'View:SecurityScan', 'url' => fn () => SecurityScan::getUrl()],
            ['label' => 'Jual Tiket Event', 'icon' => Heroicon::OutlinedTicket, 'permission' => 'View:SellTicket', 'url' => fn () => SellTicket::getUrl()],
            ['label' => 'Jual Produk Bazar', 'icon' => Heroicon::OutlinedBuildingStorefront, 'permission' => 'View:SellVendorProduct', 'url' => fn () => SellVendorProduct::getUrl()],
            ['label' => 'Short Link', 'icon' => Heroicon::OutlinedLink, 'permission' => 'Create:ShortLink', 'url' => fn () => ShortLinkResource::getUrl('create')],
            ['label' => 'Generate Barcode', 'icon' => Heroicon::OutlinedQrCode, 'permission' => 'View:GenerateBarcode', 'url' => fn () => GenerateBarcode::getUrl()],
        ])
            ->filter(fn (array $link): bool => $user?->can($link['permission']) ?? false)
            ->map(fn (array $link): array => [
                'label' => $link['label'],
                'icon' => $link['icon'],
                'url' => $link['url'](),
            ])
            ->values()
            ->all();
    }
}
