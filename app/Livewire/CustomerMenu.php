<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\CustomerOrder;
use App\Models\DiningTable;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Component;

#[Layout('layouts.guest')]
class CustomerMenu extends Component
{
    public DiningTable $table;

    /** @var array<string, array> */
    public array $cart = [];

    // Options modal
    public ?int $modalProductId = null;

    /** @var array<int> */
    public array $selectedOptions = [];

    public int $modalQty = 1;

    public string $customerName = '';

    public string $note = '';

    public bool $showCart = false;

    // Persists across page loads for this browser — lets a guest with no account
    // see the status of orders they already sent from this table.
    #[Session]
    public array $myOrderIds = [];

    public function mount(string $token): void
    {
        $this->table = DiningTable::where('qr_token', $token)->where('is_active', true)->firstOrFail();
    }

    /** @return array<int, mixed> */
    public function getCategoriesProperty()
    {
        return Category::where('is_active', true)
            ->orderBy('sort')
            ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->with('options')])
            ->get();
    }

    public function getModalProductProperty(): ?Product
    {
        return $this->modalProductId ? Product::with('options')->find($this->modalProductId) : null;
    }

    /** @return \Illuminate\Support\Collection<int, CustomerOrder> */
    public function getMyOrdersProperty()
    {
        if (empty($this->myOrderIds)) {
            return collect();
        }

        return CustomerOrder::whereIn('id', $this->myOrderIds)
            ->with('items')
            ->latest('id')
            ->get();
    }

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

        $this->showCart = true;
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

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($line) => $line['unit_price'] * $line['qty']);
    }

    public function submitOrder(): void
    {
        if (empty($this->cart)) {
            return;
        }

        $order = CustomerOrder::create([
            'dining_table_id' => $this->table->id,
            'customer_name' => $this->customerName ?: null,
            'note' => $this->note ?: null,
            'status' => CustomerOrder::STATUS_PENDING,
        ]);

        foreach ($this->cart as $line) {
            $order->items()->create([
                'product_id' => $line['product_id'],
                'product_name' => $line['name'],
                'section' => $line['section'],
                'quantity' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['unit_price'] * $line['qty'],
                'options' => $line['options'],
            ]);
        }

        $this->myOrderIds = [...$this->myOrderIds, $order->id];
        $this->cart = [];
        $this->note = '';
        $this->showCart = false;
    }

    public function render()
    {
        return view('livewire.customer-menu');
    }
}
