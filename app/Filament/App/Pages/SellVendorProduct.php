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

    /**
     * The cart being built up before checkout — a plain list of
     * {vendorProductId, quantity} pairs. Display info (names, price) is
     * always recomputed from the DB via cartItems() rather than cached
     * here, so it can never go stale if a product happens to change mid-cart.
     *
     * @var array<int, array{vendorProductId: int, quantity: int}>
     */
    public array $cart = [];

    public ?string $lastTransactionNumber = null;

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
     * Live preview only — VendorSale::sellCartFor() is still the sole
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

    /**
     * Hydrates $cart into display-ready rows (vendor name, product name,
     * quantity + unit, computed price) plus its own array index so the
     * "hapus" button can target the right entry.
     *
     * @return array<int, array{index: int, vendorName: string, productName: string, quantity: int, unitSuffix: string, price: int}>
     */
    #[Computed]
    public function cartItems(): array
    {
        $productIds = collect($this->cart)->pluck('vendorProductId')->unique();

        $products = VendorProduct::query()->with('vendor')->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($this->cart)
            ->map(function (array $entry, int $index) use ($products): ?array {
                $product = $products->get($entry['vendorProductId']);

                if (! $product) {
                    return null;
                }

                return [
                    'index' => $index,
                    'vendorName' => $product->vendor->name,
                    'productName' => $product->name,
                    'quantity' => $entry['quantity'],
                    'unitSuffix' => $product->pricing_unit->unitSuffix(),
                    'price' => $product->priceFor($entry['quantity']),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    #[Computed]
    public function cartTotal(): int
    {
        return collect($this->cartItems())->sum('price');
    }

    /**
     * @return Collection<int, VendorSale>
     */
    #[Computed]
    public function lastSaleItems(): Collection
    {
        return $this->lastTransactionNumber
            ? VendorSale::query()
                ->with(['bazaar', 'vendor', 'vendorProduct', 'soldByUser'])
                ->where('transaction_number', $this->lastTransactionNumber)
                ->get()
            : VendorSale::hydrate([]);
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
        // A cart only ever belongs to one bazaar — switching bazaars mid-cart
        // would leave stale items that sellCartFor() can't check out together.
        $this->reset(['vendorId', 'vendorProductId', 'cart']);
    }

    public function updatedVendorId(): void
    {
        $this->reset(['vendorProductId']);
    }

    public function addToCart(): void
    {
        if (! $this->vendorProductId) {
            Notification::make()->warning()->title('Pilih produk dulu.')->send();

            return;
        }

        if (! $this->quantity || $this->quantity < 1) {
            Notification::make()->warning()->title('Isi jumlah/berat dulu.')->send();

            return;
        }

        $this->cart[] = [
            'vendorProductId' => $this->vendorProductId,
            'quantity' => $this->quantity,
        ];

        // Keep bazaarId/vendorId selected — a cashier typically adds several
        // items from the same kios in a row. Only the item-specific fields
        // reset, ready for the next product.
        $this->reset(['vendorProductId', 'quantity']);
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);

        $this->cart = array_values($this->cart);
    }

    public function checkout(): void
    {
        if ($this->cart === []) {
            Notification::make()->warning()->title('Keranjang masih kosong.')->send();

            return;
        }

        if (! $this->paymentMethod) {
            Notification::make()->warning()->title('Pilih metode pembayaran dulu.')->send();

            return;
        }

        $bazaar = Bazaar::query()->findOrFail($this->bazaarId);

        $products = VendorProduct::query()
            ->whereIn('id', collect($this->cart)->pluck('vendorProductId'))
            ->get()
            ->keyBy('id');

        // A product could have been removed from the bazaar (via the admin
        // Repeater) while it was still sitting in this cart — fail loudly
        // rather than passing a null product into sellCartFor().
        $missingProduct = collect($this->cart)->contains(fn (array $entry): bool => ! $products->has($entry['vendorProductId']));

        if ($missingProduct) {
            Notification::make()->warning()->title('Salah satu produk di keranjang sudah tidak tersedia. Hapus dari keranjang lalu coba lagi.')->send();

            return;
        }

        $items = collect($this->cart)
            ->map(function (array $entry) use ($products): array {
                $product = $products->get($entry['vendorProductId']);

                if (! $product) {
                    throw new RuntimeException('Salah satu produk di keranjang sudah tidak tersedia. Hapus dari keranjang lalu coba lagi.');
                }

                return ['product' => $product, 'quantity' => $entry['quantity']];
            })
            ->all();

        /** @var User $cashier */
        $cashier = Auth::user();

        try {
            $sales = VendorSale::sellCartFor(
                bazaar: $bazaar,
                items: $items,
                cashier: $cashier,
                paymentMethod: TicketPaymentMethod::from($this->paymentMethod),
            );
        } catch (RuntimeException $exception) {
            Notification::make()->warning()->title($exception->getMessage())->send();

            return;
        }

        $this->lastTransactionNumber = $sales->first()->transaction_number;

        $this->reset(['bazaarId', 'vendorId', 'vendorProductId', 'quantity', 'paymentMethod', 'cart']);
    }

    public function nextSale(): void
    {
        $this->lastTransactionNumber = null;
    }
}
