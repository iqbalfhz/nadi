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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use RuntimeException;
use UnitEnum;

class SellVendorProduct extends Page
{
    use HasPageShield;

    protected string $view = 'filament.app.pages.sell-vendor-product';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Bazar');
    }

    public static function getNavigationLabel(): string
    {
        return __('Jual Produk Bazar');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Jual Produk Bazar');
    }

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
     * PB1 is per line, not per cart: the rate belongs to the kios, so a cart
     * spanning two stalls can carry two different rates at once.
     *
     * @return array<int, array{index: int, vendorName: string, productName: string, quantity: int, unitSuffix: string, price: int, taxRate: float, tax: int}>
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

                $price = $product->priceFor($entry['quantity']);

                return [
                    'index' => $index,
                    'vendorName' => $product->vendor->name,
                    'productName' => $product->name,
                    'quantity' => $entry['quantity'],
                    'unitSuffix' => $product->pricing_unit->unitSuffix(),
                    'price' => $price,
                    'taxRate' => (float) $product->vendor->tax_rate,
                    'tax' => $product->vendor->taxFor($price),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Before PB1 — this is also what the kios itself is owed.
     */
    #[Computed]
    public function cartSubtotal(): int
    {
        return collect($this->cartItems())->sum('price');
    }

    #[Computed]
    public function cartTax(): int
    {
        return collect($this->cartItems())->sum('tax');
    }

    /**
     * What the customer hands over.
     */
    #[Computed]
    public function cartTotal(): int
    {
        return $this->cartSubtotal() + $this->cartTax();
    }

    /**
     * Receipt figures, computed here rather than in the Blade view: the
     * print block is plain HTML by necessity (it goes to a thermal printer),
     * and keeping arithmetic out of it leaves nothing there to get wrong.
     */
    #[Computed]
    public function receiptSubtotal(): int
    {
        return (int) $this->lastSaleItems()->sum('price');
    }

    #[Computed]
    public function receiptTax(): int
    {
        return (int) $this->lastSaleItems()->sum('tax_amount');
    }

    #[Computed]
    public function receiptTotal(): int
    {
        return $this->receiptSubtotal() + $this->receiptTax();
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
            Notification::make()->warning()->title(__('Pilih produk dulu.'))->send();

            return;
        }

        if (! $this->quantity || $this->quantity < 1) {
            Notification::make()->warning()->title(__('Isi jumlah/berat dulu.'))->send();

            return;
        }

        $product = VendorProduct::query()->findOrFail($this->vendorProductId);

        // Informational check only — sellCartFor() re-checks stock for real
        // (locked, fresh from the DB) at checkout, since stock can change
        // between adding to cart and paying. This just gives the cashier
        // immediate feedback instead of a surprise at checkout. Quantities
        // already staged in the cart for this same product count against the
        // limit too.
        if ($product->initial_stock !== null) {
            $alreadyInCart = collect($this->cart)
                ->where('vendorProductId', $product->id)
                ->sum('quantity');

            $remaining = $product->initial_stock - $product->soldQuantity() - $alreadyInCart;

            if ($this->quantity > $remaining) {
                Notification::make()->warning()->title("Stok {$product->name} tidak cukup (sisa {$remaining}).")->send();

                return;
            }
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
            Notification::make()->warning()->title(__('Keranjang masih kosong.'))->send();

            return;
        }

        if (! $this->paymentMethod) {
            Notification::make()->warning()->title(__('Pilih metode pembayaran dulu.'))->send();

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
            Notification::make()->warning()->title(__('Salah satu produk di keranjang sudah tidak tersedia. Hapus dari keranjang lalu coba lagi.'))->send();

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
        } catch (ModelNotFoundException) {
            // Caught before RuntimeException on purpose: firstOrFail()
            // throws a subclass of it, and its own message is the
            // English "No query results for model [App\Models...]" —
            // meaningless to a cashier mid-transaction.
            Notification::make()->warning()->title(__('Salah satu data di keranjang sudah tidak ada. Muat ulang halaman lalu coba lagi.'))->send();

            return;
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
