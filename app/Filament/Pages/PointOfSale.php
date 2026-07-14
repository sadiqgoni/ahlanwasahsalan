<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Charge;
use App\Models\CustomerOrderItem;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use UnitEnum;

class PointOfSale extends Page
{
    protected string $view = 'filament.pages.point-of-sale';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Sales Control';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Point of Sale';

    /** @var array<string, array> */
    public array $cart = [];

    // Options modal
    public ?int $modalProductId = null;

    /** @var array<int> */
    public array $selectedOptions = [];

    public int $modalQty = 1;

    // Payment modal
    public bool $showPayment = false;

    public string $paymentMethod = 'cash';

    public ?string $amountPaid = null;

    public string $paymentReference = '';

    // Shift management
    public ?string $openingFloat = '0';

    public bool $showCloseShift = false;

    public ?string $countedCash = null;

    public string $closeNotes = '';

    // Charging a table's QR-ordered tab — set via the "Charge this table" link on Table Orders.
    #[Url]
    public ?int $chargeTable = null;

    public ?int $activeTableId = null;

    public ?string $activeTableName = null;

    public static function canAccess(): bool
    {
        return ! auth()->user()->isAccountant();
    }

    public function mount(): void
    {
        if ($this->chargeTable) {
            $this->loadTableTab($this->chargeTable);
        }
    }

    protected function getViewData(): array
    {
        return [
            'shift' => Shift::currentFor(auth()->id()),
            'categories' => Category::where('is_active', true)
                ->orderBy('sort')
                ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->with('options')])
                ->get(),
            'modalProduct' => $this->modalProductId
                ? Product::with('options')->find($this->modalProductId)
                : null,
        ];
    }

    // ---------------- Table tabs (QR ordering) ----------------

    /** Pulls a table's accepted-but-unpaid QR order items into the cart, ready to charge. */
    public function loadTableTab(int $tableId): void
    {
        $table = DiningTable::find($tableId);

        if (! $table) {
            return;
        }

        $this->activeTableId = $table->id;
        $this->activeTableName = $table->name;

        foreach ($table->openTabItems() as $item) {
            $this->cart['coi-'.$item->id] = [
                'product_id' => $item->product_id,
                'category_id' => $item->product?->category_id,
                'name' => $item->product_name,
                'section' => $item->section,
                'options' => $item->options ?? [],
                'unit_price' => (float) $item->unit_price,
                'qty' => $item->quantity,
                'customer_order_item_id' => $item->id,
            ];
        }

        $this->chargeLinesMemo = null;
    }

    public function clearTableContext(): void
    {
        $this->activeTableId = null;
        $this->activeTableName = null;
        $this->chargeTable = null;
    }

    // ---------------- Shift ----------------

    public function openShift(): void
    {
        if (Shift::currentFor(auth()->id())) {
            return;
        }

        Shift::create([
            'user_id' => auth()->id(),
            'opening_float' => (float) ($this->openingFloat ?: 0),
            'opened_at' => now(),
        ]);

        Notification::make()->title('Shift opened · An buɗe aiki — good sales!')->success()->send();
    }

    public function closeShift(): void
    {
        $shift = Shift::currentFor(auth()->id());

        if (! $shift) {
            return;
        }

        $this->validate(['countedCash' => 'required|numeric|min:0']);

        $expected = (float) $shift->opening_float + $shift->cashSalesTotal();
        $counted = (float) $this->countedCash;

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'variance' => $counted - $expected,
            'notes' => $this->closeNotes ?: null,
        ]);

        $this->showCloseShift = false;
        $this->reset('countedCash', 'closeNotes', 'cart');
        $this->chargeLinesMemo = null;

        $this->dispatch('open-receipt', url: route('shift.report', $shift));

        Notification::make()->title('Shift closed · An rufe aiki — Z-report ready')->success()->send();
    }

    // ---------------- Cart ----------------

    public function selectProduct(int $productId): void
    {
        $product = Product::with('options')->find($productId);

        if (! $product) {
            return;
        }

        if ($product->options->where('is_active', true)->isEmpty()) {
            $this->addToCart($product, []);

            return;
        }

        $this->modalProductId = $productId;
        $this->selectedOptions = [];
        $this->modalQty = 1;
    }

    public function toggleOption(int $optionId): void
    {
        if (in_array($optionId, $this->selectedOptions)) {
            $this->selectedOptions = array_values(array_diff($this->selectedOptions, [$optionId]));
        } else {
            $this->selectedOptions[] = $optionId;
        }
    }

    public function confirmOptions(): void
    {
        $product = Product::with('options')->find($this->modalProductId);

        if ($product) {
            $this->addToCart($product, $this->selectedOptions, max(1, $this->modalQty));
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->modalProductId = null;
        $this->selectedOptions = [];
        $this->modalQty = 1;
    }

    protected function addToCart(Product $product, array $optionIds, int $qty = 1): void
    {
        $options = $product->options
            ->whereIn('id', $optionIds)
            ->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price' => (float) $o->price])
            ->values()
            ->all();

        sort($optionIds);
        $key = $product->id.'-'.md5(implode(',', $optionIds));

        $unitPrice = (float) $product->price + array_sum(array_column($options, 'price'));

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += $qty;
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'section' => $product->category->name,
                'options' => $options,
                'unit_price' => $unitPrice,
                'qty' => $qty,
            ];
        }

        $this->chargeLinesMemo = null;
    }

    public function incrementLine(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
            $this->chargeLinesMemo = null;
        }
    }

    public function decrementLine(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']--;

            if ($this->cart[$key]['qty'] < 1) {
                unset($this->cart[$key]);
            }

            $this->chargeLinesMemo = null;
        }
    }

    public function removeLine(string $key): void
    {
        unset($this->cart[$key]);
        $this->chargeLinesMemo = null;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->chargeLinesMemo = null;
        $this->clearTableContext();
    }

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($line) => $line['unit_price'] * $line['qty']);
    }

    /** Computed once per request — the blade reads it several times (footer, modal, total). */
    protected ?array $chargeLinesMemo = null;

    /**
     * Owner-configured charges (VAT, service charge, …) applied to this cart.
     * A charge tied to a section only taxes the lines from that section.
     *
     * @return array<int, array{name: string, amount: float}>
     */
    public function getChargeLinesProperty(): array
    {
        if ($this->chargeLinesMemo !== null) {
            return $this->chargeLinesMemo;
        }

        if (empty($this->cart)) {
            return $this->chargeLinesMemo = [];
        }

        $lines = collect($this->cart);

        return $this->chargeLinesMemo = Charge::where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(function (Charge $charge) use ($lines) {
                $base = $lines
                    ->when($charge->category_id, fn ($l) => $l->where('category_id', $charge->category_id))
                    ->sum(fn ($line) => $line['unit_price'] * $line['qty']);

                $amount = $charge->amountFor((float) $base);

                return $amount > 0 ? ['name' => $charge->name, 'amount' => $amount] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getCartTotalProperty(): float
    {
        return $this->cartSubtotal + collect($this->chargeLines)->sum('amount');
    }

    // Barcode scanners type the code then send Enter — an exact match adds instantly.
    // Search/filtering itself is client-side now; the server only sees the Enter key.
    public function scanOrSearch(string $code = ''): void
    {
        $code = trim($code);

        if ($code === '') {
            return;
        }

        $product = Product::where('barcode', $code)->where('is_active', true)->first();

        if ($product) {
            $this->selectProduct($product->id);
            $this->dispatch('barcode-matched');
        }
    }

    // ---------------- Payment ----------------

    public function startPayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Cart is empty')->warning()->send();

            return;
        }

        $this->paymentMethod = 'cash';
        $this->amountPaid = null;
        $this->paymentReference = '';
        $this->showPayment = true;
    }

    public function completeSale(): void
    {
        $shift = Shift::currentFor(auth()->id());

        if (! $shift || empty($this->cart)) {
            return;
        }

        if ($this->paymentMethod !== 'cash') {
            $this->validate(['paymentReference' => 'required|string'], [
                'paymentReference.required' => 'Enter the transfer/POS reference so it can be matched with the bank statement.',
            ]);
        }

        // Cash: the cashier must type (or tap) the amount actually received —
        // never assume exact payment, that is how change mistakes happen.
        if ($this->paymentMethod === 'cash') {
            $this->validate(['amountPaid' => 'required|numeric|min:0'], [
                'amountPaid.required' => 'Enter the amount the customer gave you · Shigar da kuɗin da aka baka.',
                'amountPaid.numeric' => 'Amount must be a number.',
            ]);
        }

        $total = $this->cartTotal;
        $paid = $this->paymentMethod === 'cash' ? (float) $this->amountPaid : $total;

        if ($this->paymentMethod === 'cash' && $paid < $total) {
            $this->dispatch('pos-error');
            Notification::make()->title('Amount paid is less than total · Kuɗin bai kai jimla ba')->danger()->send();

            return;
        }

        $subtotal = $this->cartSubtotal;
        $chargeLines = $this->chargeLines;
        $tableId = $this->activeTableId;

        $sale = DB::transaction(function () use ($shift, $total, $subtotal, $chargeLines, $paid, $tableId) {
            $sale = Sale::create([
                'receipt_no' => Sale::nextReceiptNo(),
                'shift_id' => $shift->id,
                'dining_table_id' => $tableId,
                'user_id' => auth()->id(),
                'total' => $total,
                'subtotal' => $subtotal,
                'charges' => $chargeLines,
                'amount_paid' => $paid,
                'change_due' => $this->paymentMethod === 'cash' ? max(0, $paid - $total) : 0,
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->paymentReference ?: null,
                'status' => 'completed',
            ]);

            foreach ($this->cart as $line) {
                $saleItem = $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'section' => $line['section'],
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['unit_price'] * $line['qty'],
                    'options' => $line['options'],
                ]);

                // Line came from a customer's QR order — mark it charged so it drops off the table's open tab.
                if (isset($line['customer_order_item_id'])) {
                    CustomerOrderItem::where('id', $line['customer_order_item_id'])
                        ->update(['sale_item_id' => $saleItem->id]);
                }
            }

            return $sale;
        });

        $this->cart = [];
        $this->chargeLinesMemo = null;
        $this->showPayment = false;
        $this->clearTableContext();

        // Open the receipt in a popup and stay on the POS — the next customer is waiting.
        $this->dispatch('sale-completed', receiptUrl: route('receipt.print', $sale));

        Notification::make()
            ->title("Receipt {$sale->receipt_no} — ₦".number_format($total))
            ->success()
            ->send();
    }
}
