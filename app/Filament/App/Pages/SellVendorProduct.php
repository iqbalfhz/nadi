<?php

namespace App\Filament\App\Pages;

use App\Enums\TicketPaymentMethod;
use App\Models\Bazaar;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorSale;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use RuntimeException;
use UnitEnum;

class SellVendorProduct extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.sell-vendor-product';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Bazar';

    protected static ?string $navigationLabel = 'Jual Produk Bazar';

    protected static ?string $title = 'Jual Produk Bazar';

    public ?int $bazaarId = null;

    public ?int $vendorId = null;

    public ?int $vendorProductId = null;

    public ?int $quantity = null;

    public ?string $paymentMethod = null;

    public ?int $lastSaleId = null;

    /**
     * @return Collection<int, Bazaar>
     */
    #[Computed]
    public function openBazaars(): Collection
    {
        return Bazaar::query()->where('is_open', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Vendor>
     */
    #[Computed]
    public function vendorsForSelectedBazaar(): Collection
    {
        return $this->bazaarId
            ? Vendor::query()->where('bazaar_id', $this->bazaarId)->orderBy('name')->get()
            : Vendor::hydrate([]);
    }

    /**
     * @return Collection<int, VendorProduct>
     */
    #[Computed]
    public function productsForSelectedVendor(): Collection
    {
        return $this->vendorId
            ? VendorProduct::query()->where('vendor_id', $this->vendorId)->orderBy('name')->get()
            : VendorProduct::hydrate([]);
    }

    #[Computed]
    public function selectedProduct(): ?VendorProduct
    {
        return $this->vendorProductId
            ? $this->productsForSelectedVendor()->firstWhere('id', $this->vendorProductId)
            : null;
    }

    /**
     * Live preview only — VendorSale::sellFor() is still the sole
     * authoritative computation, recomputed server-side from a locked row.
     */
    #[Computed]
    public function estimatedPrice(): ?int
    {
        $product = $this->selectedProduct();

        return ($product && $this->quantity && $this->quantity > 0)
            ? $product->priceFor($this->quantity)
            : null;
    }

    #[Computed]
    public function lastSale(): ?VendorSale
    {
        return $this->lastSaleId
            ? VendorSale::query()->with(['bazaar', 'vendor', 'vendorProduct', 'soldByUser'])->find($this->lastSaleId)
            : null;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return collect(TicketPaymentMethod::cases())
            ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()])
            ->all();
    }

    public function updatedBazaarId(): void
    {
        $this->reset(['vendorId', 'vendorProductId']);
    }

    public function updatedVendorId(): void
    {
        $this->reset(['vendorProductId']);
    }

    public function sell(): void
    {
        if (! $this->vendorProductId) {
            Notification::make()->warning()->title('Pilih produk dulu.')->send();

            return;
        }

        if (! $this->quantity || $this->quantity < 1) {
            Notification::make()->warning()->title('Isi jumlah/berat dulu.')->send();

            return;
        }

        if (! $this->paymentMethod) {
            Notification::make()->warning()->title('Pilih metode pembayaran dulu.')->send();

            return;
        }

        $product = VendorProduct::query()->findOrFail($this->vendorProductId);

        /** @var User $cashier */
        $cashier = Auth::user();

        try {
            $sale = VendorSale::sellFor(
                product: $product,
                cashier: $cashier,
                quantity: $this->quantity,
                paymentMethod: TicketPaymentMethod::from($this->paymentMethod),
            );
        } catch (RuntimeException $exception) {
            Notification::make()->warning()->title($exception->getMessage())->send();

            return;
        }

        $this->lastSaleId = $sale->id;

        // Keep bazaarId/vendorId/vendorProductId selected — a cashier
        // typically sells the same product repeatedly to a queue of buyers,
        // only quantity/paymentMethod reset (mirrors SellTicket::sell()).
        $this->reset(['quantity', 'paymentMethod']);
    }

    public function nextSale(): void
    {
        $this->lastSaleId = null;
    }
}
