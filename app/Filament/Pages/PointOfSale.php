<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
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

    public ?int $activeCategory = null;

    public string $search = '';

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

    public static function canAccess(): bool
    {
        return ! auth()->user()->isAccountant();
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
                'name' => $product->name,
                'section' => $product->category->name,
                'options' => $options,
                'unit_price' => $unitPrice,
                'qty' => $qty,
            ];
        }
    }

    public function incrementLine(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
        }
    }

    public function decrementLine(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']--;

            if ($this->cart[$key]['qty'] < 1) {
                unset($this->cart[$key]);
            }
        }
    }

    public function removeLine(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($line) => $line['unit_price'] * $line['qty']);
    }

    // Barcode scanners type the code then send Enter — an exact match adds instantly.
    public function scanOrSearch(): void
    {
        $code = trim($this->search);

        if ($code === '') {
            return;
        }

        $product = Product::where('barcode', $code)->where('is_active', true)->first();

        if ($product) {
            $this->selectProduct($product->id);
            $this->search = '';
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

        $total = $this->cartTotal;
        $paid = filled($this->amountPaid) ? (float) $this->amountPaid : $total;

        if ($this->paymentMethod === 'cash' && $paid < $total) {
            $this->dispatch('pos-error');
            Notification::make()->title('Amount paid is less than total · Kuɗin bai kai jimla ba')->danger()->send();

            return;
        }

        $sale = DB::transaction(function () use ($shift, $total, $paid) {
            $sale = Sale::create([
                'receipt_no' => Sale::nextReceiptNo(),
                'shift_id' => $shift->id,
                'user_id' => auth()->id(),
                'total' => $total,
                'amount_paid' => $paid,
                'change_due' => $this->paymentMethod === 'cash' ? max(0, $paid - $total) : 0,
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->paymentReference ?: null,
                'status' => 'completed',
            ]);

            foreach ($this->cart as $line) {
                $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'section' => $line['section'],
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['unit_price'] * $line['qty'],
                    'options' => $line['options'],
                ]);
            }

            return $sale;
        });

        $this->cart = [];
        $this->showPayment = false;

        $this->dispatch('open-receipt', url: route('receipt.print', $sale));

        Notification::make()
            ->title("Receipt {$sale->receipt_no} — ₦".number_format($total))
            ->success()
            ->send();
    }
}
