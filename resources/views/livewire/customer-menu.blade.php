@php
    $cartCount = collect($cart)->sum('qty');
    $cartTotal = $this->cartTotal;
    $categories = $this->categories;
    $modalProduct = $this->modalProduct;
    $myOrders = $this->myOrders;
@endphp
<div
    class="cm-page"
    x-data="{
        q: '',
        cat: 'all',
        show(name, catId) {
            return (this.cat === 'all' || this.cat === catId)
                && (this.q === '' || name.includes(this.q.toLowerCase().trim()));
        },
    }"
>

    <header class="cm-header">
        <div class="cm-header-mark">🍛</div>
        <div>
            <div class="cm-header-name">{{ config('app.name', 'Ahlan wa Sahlan') }}</div>
            <div class="cm-header-table">{{ $table->name }} <span class="cm-ha">· Tebur</span></div>
        </div>
    </header>

    @if ($myOrders->isNotEmpty())
        <div class="cm-orders" wire:poll.5s>
            <div class="cm-orders-title">Your orders <span class="cm-ha">· Odar ka</span></div>
            @foreach ($myOrders as $order)
                <div class="cm-order-row cm-order-{{ $order->status }}">
                    <div>
                        <strong>₦{{ number_format($order->total()) }}</strong>
                        <span class="cm-order-items">{{ $order->items->sum('quantity') }} item(s)</span>
                    </div>
                    <span class="cm-order-badge">
                        @if ($order->status === 'pending')
                            ⏳ Waiting for confirmation <span class="cm-ha">· Ana jira</span>
                        @elseif ($order->status === 'accepted')
                            ✅ Being prepared <span class="cm-ha">· Ana shiryawa</span>
                        @else
                            ✕ Not accepted <span class="cm-ha">· An ki</span>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="cm-searchbar">
        <input type="text" x-model="q" placeholder="Search menu / Nemo abinci…" autocomplete="off" />
    </div>

    <div class="cm-tabs">
        <button type="button" x-on:click="cat = 'all'" :class="{ active: cat === 'all' }" class="cm-tab">All</button>
        @foreach ($categories as $category)
            <button type="button" x-on:click="cat = {{ $category->id }}" :class="{ active: cat === {{ $category->id }} }" class="cm-tab" style="--tab-color: {{ $category->color }}">{{ $category->name }}</button>
        @endforeach
    </div>

    <div class="cm-grid">
        @foreach ($categories as $category)
            @foreach ($category->products as $product)
                <button
                    type="button"
                    wire:key="cm-tile-{{ $product->id }}"
                    wire:click="selectProduct({{ $product->id }})"
                    x-show="show(@js(mb_strtolower($product->name)), {{ $category->id }})"
                    class="cm-item"
                    style="--item-color: {{ $category->color }}"
                >
                    <span class="cm-item-img">
                        @if ($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" />
                        @else
                            <span class="cm-item-img-fallback">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                        @endif
                        <span class="cm-item-section">{{ $category->name }}</span>
                    </span>
                    <span class="cm-item-body">
                        <span class="cm-item-name">{{ $product->name }}</span>
                        <span class="cm-item-price">₦{{ number_format((float) $product->price) }}</span>
                    </span>
                </button>
            @endforeach
        @endforeach
    </div>

    {{-- ===================== CART BAR ===================== --}}
    @if ($cartCount > 0)
        <button type="button" wire:click="$set('showCart', true)" class="cm-cart-bar">
            <span>{{ $cartCount }} item(s)</span>
            <span>View order · Duba oda — ₦{{ number_format($cartTotal) }}</span>
        </button>
    @endif

    {{-- ===================== OPTIONS MODAL ===================== --}}
    @if ($modalProduct)
        <div class="cm-overlay" wire:click.self="closeModal">
            <div class="cm-sheet">
                @if ($modalProduct->imageUrl())
                    <img src="{{ $modalProduct->imageUrl() }}" alt="{{ $modalProduct->name }}" class="cm-sheet-img" />
                @endif
                <h3>{{ $modalProduct->name }} <small>₦{{ number_format((float) $modalProduct->price) }} base</small></h3>
                @foreach ($modalProduct->options->where('is_active', true)->groupBy('group') as $group => $options)
                    <div class="cm-opt-group">{{ $group }}</div>
                    <div class="cm-opt-list">
                        @foreach ($options as $option)
                            <button type="button" wire:click="toggleOption({{ $option->id }})" class="cm-opt {{ in_array($option->id, $selectedOptions) ? 'selected' : '' }}">
                                {{ $option->name }}
                                <span>+₦{{ number_format((float) $option->price) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach

                <div class="cm-qty-row">
                    <span>Quantity <span class="cm-ha">· Adadi</span></span>
                    <div class="cm-qty">
                        <button type="button" wire:click="$set('modalQty', {{ max(1, $modalQty - 1) }})">−</button>
                        <span>{{ $modalQty }}</span>
                        <button type="button" wire:click="$set('modalQty', {{ $modalQty + 1 }})">+</button>
                    </div>
                </div>

                @php
                    $optTotal = $modalProduct->options->whereIn('id', $selectedOptions)->sum('price');
                    $modalUnit = (float) $modalProduct->price + (float) $optTotal;
                @endphp

                <div class="cm-sheet-actions">
                    <button type="button" wire:click="closeModal" class="cm-btn-ghost">Cancel</button>
                    <button type="button" wire:click="confirmOptions" class="cm-btn-primary">Add — ₦{{ number_format($modalUnit * max(1, $modalQty)) }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== CART SHEET ===================== --}}
    @if ($showCart)
        <div class="cm-overlay" wire:click.self="$set('showCart', false)">
            <div class="cm-sheet">
                <h3>Your Order <span class="cm-ha">· Odarka</span></h3>

                @forelse ($cart as $key => $line)
                    <div class="cm-line" wire:key="line-{{ $key }}">
                        <div class="cm-line-info">
                            <span class="cm-line-name">{{ $line['name'] }}</span>
                            @if (! empty($line['options']))
                                <span class="cm-line-opts">
                                    @foreach ($line['options'] as $opt)
                                        + {{ $opt['name'] }}@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            @endif
                        </div>
                        <div class="cm-qty">
                            <button type="button" wire:click="decrementLine('{{ $key }}')">−</button>
                            <span>{{ $line['qty'] }}</span>
                            <button type="button" wire:click="incrementLine('{{ $key }}')">+</button>
                        </div>
                        <div class="cm-line-total">₦{{ number_format($line['unit_price'] * $line['qty']) }}</div>
                    </div>
                @empty
                    <p class="cm-empty">Your cart is empty · Babu abinci a cikin oda</p>
                @endforelse

                @if (! empty($cart))
                    <label>Your name (optional) <span class="cm-ha">· Sunanka</span></label>
                    <input type="text" wire:model="customerName" placeholder="So the waiter knows who to bring it to" />

                    <label>Note for the kitchen (optional) <span class="cm-ha">· Bayani</span></label>
                    <input type="text" wire:model="note" placeholder="e.g. no pepper / ba tattasai" />

                    <div class="cm-total-row"><span>TOTAL</span><span>₦{{ number_format($cartTotal) }}</span></div>

                    <div class="cm-sheet-actions">
                        <button type="button" wire:click="$set('showCart', false)" class="cm-btn-ghost">Keep browsing</button>
                        <button type="button" wire:click="submitOrder" wire:loading.attr="disabled" wire:target="submitOrder" class="cm-btn-primary">
                            <span wire:loading.remove wire:target="submitOrder">Send to Kitchen · Aika Oda</span>
                            <span wire:loading wire:target="submitOrder">Sending…</span>
                        </button>
                    </div>
                    <p class="cm-note">Staff will confirm your order before it's prepared. You pay at the counter when you're done. <br><em>Ma'aikaci zai tabbatar da oda kafin a shirya. Za ka biya a kanti bayan ka gama.</em></p>
                @else
                    <div class="cm-sheet-actions">
                        <button type="button" wire:click="$set('showCart', false)" class="cm-btn-primary">Back to menu</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #faf6ee; color: #1c1917; }
        .cm-page { max-width: 560px; margin: 0 auto; padding: 1rem 1rem 6rem; }
        .cm-ha { opacity: .6; font-weight: 500; font-size: .85em; font-style: italic; }

        .cm-header { display: flex; align-items: center; gap: .7rem; padding: .5rem 0 1rem; }
        .cm-header-mark { width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: linear-gradient(135deg,#f59e0b,#b45309); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(217,119,6,.35); }
        .cm-header-name { font-weight: 800; font-size: 1.05rem; }
        .cm-header-table { font-size: .8rem; opacity: .65; font-weight: 600; }

        .cm-orders { background: #fff; border: 1px solid rgba(120,120,120,.18); border-radius: .9rem; padding: .8rem 1rem; margin-bottom: 1rem; }
        .cm-orders-title { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; opacity: .55; margin-bottom: .5rem; }
        .cm-order-row { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .45rem 0; border-top: 1px dashed rgba(120,120,120,.2); flex-wrap: wrap; }
        .cm-order-row:first-of-type { border-top: none; }
        .cm-order-items { display: block; font-size: .72rem; opacity: .6; }
        .cm-order-badge { font-size: .78rem; font-weight: 700; }
        .cm-order-pending .cm-order-badge { color: #b45309; }
        .cm-order-accepted .cm-order-badge { color: #15803d; }
        .cm-order-rejected .cm-order-badge { color: #dc2626; }

        .cm-searchbar input, .cm-sheet input { width: 100%; padding: .7rem .9rem; border-radius: .6rem; border: 1px solid rgba(120,120,120,.3); background: #fff; font-size: .95rem; color: inherit; }
        .cm-searchbar { margin-bottom: .75rem; }

        .cm-tabs { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; overflow-x: auto; }
        .cm-tab { padding: .45rem 1rem; border-radius: 999px; border: 1px solid rgba(120,120,120,.3); font-size: .85rem; font-weight: 600; cursor: pointer; background: #fff; color: inherit; white-space: nowrap; }
        .cm-tab.active { background: var(--tab-color, #d97706); border-color: var(--tab-color, #d97706); color: #fff; }

        .cm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .8rem; }
        .cm-item { position: relative; display: flex; flex-direction: column; padding: 0; border-radius: .9rem; overflow: hidden; border: 1px solid rgba(120,120,120,.2); border-bottom: 4px solid var(--item-color, #f59e0b); background: #fff; cursor: pointer; text-align: left; }
        .cm-item-img { position: relative; width: 100%; aspect-ratio: 4/3; background: color-mix(in srgb, var(--item-color, #f59e0b) 16%, transparent); overflow: hidden; }
        .cm-item-img img { width: 100% !important; max-width: none !important; height: 100% !important; object-fit: cover; display: block; }
        .cm-item-img-fallback { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: var(--item-color, #f59e0b); opacity: .8; }
        .cm-item-section { position: absolute; top: .35rem; left: .35rem; font-size: .55rem; text-transform: uppercase; font-weight: 800; background: rgba(0,0,0,.55); color: #fff; padding: .15rem .45rem; border-radius: 999px; }
        .cm-item-body { padding: .5rem .6rem .65rem; }
        .cm-item-name { display: block; font-weight: 700; font-size: .85rem; line-height: 1.2; margin-bottom: .25rem; }
        .cm-item-price { color: #d97706; font-weight: 800; font-size: .88rem; }

        .cm-cart-bar { position: fixed; left: 1rem; right: 1rem; bottom: 1rem; max-width: 528px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; background: #d97706; color: #fff; border: none; border-radius: .9rem; padding: 1rem 1.2rem; font-weight: 800; font-size: .9rem; cursor: pointer; box-shadow: 0 10px 30px rgba(217,119,6,.4); }

        .cm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: flex-end; justify-content: center; z-index: 50; }
        .cm-sheet { background: #faf6ee; color: #1c1917; border-radius: 1.2rem 1.2rem 0 0; padding: 1.25rem; width: 100%; max-width: 560px; max-height: 88vh; overflow-y: auto; }
        .cm-sheet-img { width: calc(100% + 2.5rem); margin: -1.25rem -1.25rem 1rem; height: 160px; object-fit: cover; display: block; }
        .cm-sheet h3 { font-size: 1.1rem; font-weight: 800; margin: 0 0 .8rem; }
        .cm-sheet h3 small { font-weight: 500; opacity: .6; font-size: .8rem; }
        .cm-sheet label { display: block; font-size: .82rem; font-weight: 600; margin: .8rem 0 .3rem; }

        .cm-opt-group { font-size: .72rem; text-transform: uppercase; opacity: .55; font-weight: 700; margin: .7rem 0 .35rem; }
        .cm-opt-list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .cm-opt { padding: .5rem .8rem; border-radius: .6rem; border: 1.5px solid rgba(120,120,120,.3); background: #fff; cursor: pointer; font-size: .82rem; font-weight: 600; color: inherit; }
        .cm-opt span { opacity: .6; font-size: .75rem; }
        .cm-opt.selected { border-color: #d97706; background: rgba(217,119,6,.12); color: #d97706; }

        .cm-qty-row { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; font-weight: 600; font-size: .88rem; }
        .cm-qty { display: flex; align-items: center; gap: .5rem; }
        .cm-qty button { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(120,120,120,.35); background: #fff; font-weight: 700; cursor: pointer; }
        .cm-qty span { min-width: 18px; text-align: center; font-weight: 700; }

        .cm-line { display: grid; grid-template-columns: 1fr auto auto; gap: .5rem; align-items: center; padding: .55rem 0; border-bottom: 1px solid rgba(120,120,120,.12); }
        .cm-line-name { font-weight: 600; font-size: .85rem; display: block; }
        .cm-line-opts { font-size: .7rem; color: #d97706; display: block; }
        .cm-line-total { font-weight: 700; font-size: .85rem; white-space: nowrap; }
        .cm-empty { text-align: center; opacity: .55; padding: 2rem 0; }

        .cm-total-row { display: flex; justify-content: space-between; font-weight: 800; font-size: 1.1rem; margin-top: 1rem; padding-top: .8rem; border-top: 1px dashed rgba(120,120,120,.3); color: #d97706; }
        .cm-sheet-actions { display: flex; gap: .6rem; margin-top: 1.1rem; }
        .cm-btn-primary { flex: 1; padding: .8rem 1rem; border-radius: .7rem; background: #d97706; color: #fff; font-weight: 700; border: none; cursor: pointer; }
        .cm-btn-primary:disabled { opacity: .5; }
        .cm-btn-ghost { flex: 1; padding: .8rem 1rem; border-radius: .7rem; background: transparent; border: 1px solid rgba(120,120,120,.3); font-weight: 600; cursor: pointer; color: inherit; }
        .cm-note { font-size: .72rem; opacity: .6; margin-top: .9rem; text-align: center; }
    </style>
</div>
