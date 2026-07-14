<x-filament-panels::page>
    @php
        $cartSubtotal = $this->cartSubtotal;
        $chargeLines = $this->chargeLines;
        $cartTotal = $this->cartTotal;
        $cartCount = collect($cart)->sum('qty');
        // Per-product quantities already in the cart — shown as badges on the menu tiles.
        $inCart = collect($cart)->groupBy('product_id')->map(fn ($g) => $g->sum('qty'));
    @endphp

    @if (! $shift)
        {{-- ===================== OPEN SHIFT ===================== --}}
        <div class="pos-open-shift">
            <div class="pos-open-card">
                <div class="pos-open-icon">
                    <x-filament::icon icon="heroicon-o-lock-open" />
                </div>
                <h2>Open Your Shift <small class="pos-ha">· Buɗe Aikinka</small></h2>
                <p>Enter the cash float you are starting with. Every sale you record will be tied to this shift — at closing, the cash in the drawer must match the system.</p>
                <p class="pos-ha-note">Shigar da kuɗin da ka fara aiki da su. Duk sayarwar da ka yi za a lissafa a kanka — idan ka rufe aiki, kuɗin da ke akwati dole su zama daidai da na kwamfuta.</p>
                <label>Opening cash float (₦) <span class="pos-ha">· Kuɗin fara aiki</span></label>
                <input type="number" wire:model="openingFloat" min="0" step="100" placeholder="0" />
                <button type="button" wire:click="openShift" wire:loading.attr="disabled" wire:target="openShift" class="pos-btn-primary">
                    <span wire:loading.remove wire:target="openShift" class="pos-btn-inner">
                        <x-filament::icon icon="heroicon-m-play" class="pos-icon" />
                        Start Shift · Fara Aiki
                    </span>
                    <span wire:loading wire:target="openShift" class="pos-btn-inner"><span class="pos-spinner"></span> Opening…</span>
                </button>
            </div>
        </div>
    @else
        @if ($shift->opened_at->lt(today()))
            {{-- Shift left open from a previous day — Z-report will mix days --}}
            <div class="pos-stale-shift">
                <x-filament::icon icon="heroicon-s-exclamation-triangle" class="pos-icon pos-icon-lg" />
                <div>
                    This shift was opened on <strong>{{ $shift->opened_at->format('D d M, h:i A') }}</strong> and was never closed.
                    Close it now and print the Z-report, then open a fresh shift for today — otherwise today's sales will mix into that day's report.
                    <br><em>An buɗe wannan aikin tun {{ $shift->opened_at->format('d/m') }} ba a rufe ba. Ka rufe shi yanzu, sannan ka buɗe sabon aiki na yau.</em>
                </div>
            </div>
        @endif

        {{-- ===================== POS LAYOUT ===================== --}}
        <div class="pos-wrap">
            {{-- LEFT: menu — search & tabs filter instantly in the browser, no server round-trip --}}
            @php
                $menuIndex = $categories->flatMap(fn ($c) => $c->products->map(fn ($p) => ['n' => mb_strtolower($p->name), 'c' => $c->id]))->values();
            @endphp
            <div
                class="pos-menu"
                x-data="{
                    q: '',
                    cat: 'all',
                    items: {{ Illuminate\Support\Js::from($menuIndex) }},
                    show(name, catId) {
                        return (this.cat === 'all' || this.cat === catId)
                            && (this.q === '' || name.includes(this.q.toLowerCase().trim()));
                    },
                    get anyVisible() { return this.items.some(i => this.show(i.n, i.c)); },
                }"
                x-on:barcode-matched.window="q = ''"
            >
                <div class="pos-searchbar">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="pos-icon pos-search-icon" />
                    <input
                        type="text"
                        x-model="q"
                        wire:keydown.enter="scanOrSearch($event.target.value)"
                        placeholder="Search item / Nemo abinci…"
                        autocomplete="off"
                    />
                    <button type="button" x-show="q !== ''" x-cloak x-on:click="q = ''" class="pos-search-clear" title="Clear search">
                        <x-filament::icon icon="heroicon-m-x-mark" class="pos-icon" />
                    </button>
                </div>

                <div class="pos-tabs">
                    <button
                        type="button"
                        x-on:click="cat = 'all'"
                        :class="{ active: cat === 'all' }"
                        class="pos-tab"
                    >
                        <x-filament::icon icon="heroicon-m-squares-2x2" class="pos-icon" />
                        All
                    </button>
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            x-on:click="cat = {{ $category->id }}"
                            :class="{ active: cat === {{ $category->id }} }"
                            class="pos-tab"
                            style="--tab-color: {{ $category->color }}"
                        >{{ $category->name }}</button>
                    @endforeach
                </div>

                <div class="pos-grid">
                    @foreach ($categories as $category)
                        @foreach ($category->products as $product)
                            <button
                                type="button"
                                wire:key="tile-{{ $product->id }}"
                                wire:click="selectProduct({{ $product->id }})"
                                onclick="posSound('add')"
                                x-show="show(@js(mb_strtolower($product->name)), {{ $category->id }})"
                                class="pos-item"
                                style="--item-color: {{ $category->color }}"
                            >
                                <span class="pos-item-img">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" />
                                    @else
                                        <span class="pos-item-img-fallback">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                    @endif
                                    <span class="pos-item-section">{{ $category->name }}</span>
                                    @if ($inCart->has($product->id))
                                        <span class="pos-item-badge">{{ $inCart[$product->id] }}</span>
                                    @endif
                                </span>
                                <span class="pos-item-body">
                                    <span class="pos-item-name">{{ $product->name }}</span>
                                    <span class="pos-item-meta">
                                        <span class="pos-item-price">₦{{ number_format((float) $product->price) }}</span>
                                        @if ($product->options->isNotEmpty())
                                            <span class="pos-item-opts">
                                                <x-filament::icon icon="heroicon-m-adjustments-horizontal" class="pos-icon" />
                                                options
                                            </span>
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    @endforeach
                </div>

                <div class="pos-no-results" x-show="! anyVisible" x-cloak>
                    <x-filament::icon icon="heroicon-o-magnifying-glass" />
                    <p>No items match “<span x-text="q"></span>”</p>
                    <p class="pos-ha">Babu abincin da ya dace da binciken</p>
                    <button type="button" x-on:click="q = ''" class="pos-btn-ghost pos-btn-inline">Clear search · Share bincike</button>
                </div>
            </div>

            {{-- RIGHT: cart --}}
            <div class="pos-cart">
                <div class="pos-cart-head">
                    <div>
                        <strong class="pos-cart-title">
                            <x-filament::icon icon="heroicon-o-shopping-cart" class="pos-icon" />
                            Current Order <span class="pos-ha">· Oda</span>
                            @if ($cartCount > 0)
                                <span class="pos-count-badge" wire:key="count-{{ $cartCount }}">{{ $cartCount }}</span>
                            @endif
                        </strong>
                        <span class="pos-shift-badge">
                            <x-filament::icon icon="heroicon-m-clock" class="pos-icon" />
                            Shift open since {{ $shift->opened_at->format('h:i A') }}
                        </span>
                    </div>
                    @if (! empty($cart))
                        <button type="button" wire:click="clearCart" onclick="posSound('down')" class="pos-clear">
                            <x-filament::icon icon="heroicon-m-trash" class="pos-icon" />
                            Clear
                        </button>
                    @endif
                </div>

                @if ($activeTableName)
                    <div class="pos-table-banner">
                        <x-filament::icon icon="heroicon-m-qr-code" class="pos-icon" />
                        Charging <strong>{{ $activeTableName }}</strong>'s QR order — items below came from their phone.
                    </div>
                @endif

                <div class="pos-cart-lines">
                    @forelse ($cart as $key => $line)
                        <div class="pos-line" wire:key="line-{{ $key }}">
                            <div class="pos-line-info">
                                <span class="pos-line-name">{{ $line['name'] }}</span>
                                @if (! empty($line['options']))
                                    <span class="pos-line-opts">
                                        @foreach ($line['options'] as $opt)
                                            + {{ $opt['name'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </span>
                                @endif
                                <span class="pos-line-unit">₦{{ number_format($line['unit_price']) }} each</span>
                            </div>
                            <div class="pos-line-qty">
                                <button type="button" wire:click="decrementLine('{{ $key }}')" onclick="posSound('down')" title="Reduce">
                                    <x-filament::icon icon="heroicon-m-minus" class="pos-icon" />
                                </button>
                                <span wire:key="qty-{{ $key }}-{{ $line['qty'] }}">{{ $line['qty'] }}</span>
                                <button type="button" wire:click="incrementLine('{{ $key }}')" onclick="posSound('up')" title="Add one more">
                                    <x-filament::icon icon="heroicon-m-plus" class="pos-icon" />
                                </button>
                            </div>
                            <div class="pos-line-total">₦{{ number_format($line['unit_price'] * $line['qty']) }}</div>
                            <button type="button" wire:click="removeLine('{{ $key }}')" onclick="posSound('down')" class="pos-line-remove" title="Remove line">
                                <x-filament::icon icon="heroicon-m-x-mark" class="pos-icon" />
                            </button>
                        </div>
                    @empty
                        <div class="pos-cart-empty">
                            <x-filament::icon icon="heroicon-o-shopping-cart" />
                            <p>Tap items to add them to the order</p>
                            <p class="pos-ha">Danna abinci don saka a cikin oda</p>
                        </div>
                    @endforelse
                </div>

                <div class="pos-cart-foot">
                    @if (! empty($chargeLines))
                        <div class="pos-sub-row"><span>Subtotal</span><span>₦{{ number_format($cartSubtotal) }}</span></div>
                        @foreach ($chargeLines as $chargeLine)
                            <div class="pos-sub-row pos-sub-charge"><span>{{ $chargeLine['name'] }}</span><span>+₦{{ number_format($chargeLine['amount']) }}</span></div>
                        @endforeach
                    @endif
                    <div class="pos-total-row">
                        <span>TOTAL <span class="pos-ha">· JIMLA</span></span>
                        <span class="pos-total" wire:key="total-{{ $cartTotal }}">₦{{ number_format($cartTotal) }}</span>
                    </div>
                    <button type="button" wire:click="startPayment" onclick="posSound('tap')" class="pos-btn-primary pos-btn-pay" @if(empty($cart)) disabled @endif>
                        <span class="pos-btn-inner">
                            <x-filament::icon icon="heroicon-m-banknotes" class="pos-icon" />
                            Take Payment · Karɓi Kuɗi
                        </span>
                    </button>
                    <button type="button" wire:click="$set('showCloseShift', true)" class="pos-btn-close-day">
                        <span class="pos-btn-inner">
                            <x-filament::icon icon="heroicon-m-lock-closed" class="pos-icon" />
                            Close Day Sales · Rufe Sayarwar Yau / Z-Report
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ===================== OPTIONS MODAL ===================== --}}
        @if ($modalProduct)
            <div class="pos-overlay" wire:click.self="closeModal" x-data x-on:keydown.escape.window="$wire.closeModal()">
                <div class="pos-modal">
                    @if ($modalProduct->imageUrl())
                        <img src="{{ $modalProduct->imageUrl() }}" alt="{{ $modalProduct->name }}" class="pos-modal-img" />
                    @endif
                    <h3>{{ $modalProduct->name }} <small>₦{{ number_format((float) $modalProduct->price) }} base</small></h3>
                    @foreach ($modalProduct->options->where('is_active', true)->groupBy('group') as $group => $options)
                        <div class="pos-opt-group">{{ $group }}</div>
                        <div class="pos-opt-list">
                            @foreach ($options as $option)
                                <button
                                    type="button"
                                    wire:click="toggleOption({{ $option->id }})"
                                    onclick="posSound('tap')"
                                    class="pos-opt {{ in_array($option->id, $selectedOptions) ? 'selected' : '' }}"
                                >
                                    @if (in_array($option->id, $selectedOptions))
                                        <x-filament::icon icon="heroicon-m-check" class="pos-icon" />
                                    @endif
                                    {{ $option->name }}
                                    <span>+₦{{ number_format((float) $option->price) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="pos-modal-qty">
                        <span>Quantity <span class="pos-ha">· Adadi</span></span>
                        <div class="pos-line-qty">
                            <button type="button" wire:click="$set('modalQty', {{ max(1, $modalQty - 1) }})" onclick="posSound('down')">
                                <x-filament::icon icon="heroicon-m-minus" class="pos-icon" />
                            </button>
                            <span>{{ $modalQty }}</span>
                            <button type="button" wire:click="$set('modalQty', {{ $modalQty + 1 }})" onclick="posSound('up')">
                                <x-filament::icon icon="heroicon-m-plus" class="pos-icon" />
                            </button>
                        </div>
                    </div>

                    @php
                        $optTotal = $modalProduct->options->whereIn('id', $selectedOptions)->sum('price');
                        $modalUnit = (float) $modalProduct->price + (float) $optTotal;
                    @endphp

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="closeModal" class="pos-btn-ghost">Cancel</button>
                        <button type="button" wire:click="confirmOptions" onclick="posSound('add')" class="pos-btn-primary">
                            <span class="pos-btn-inner">
                                <x-filament::icon icon="heroicon-m-plus-circle" class="pos-icon" />
                                Add — ₦{{ number_format($modalUnit * max(1, $modalQty)) }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== PAYMENT MODAL ===================== --}}
        @if ($showPayment)
            <div class="pos-overlay" wire:click.self="$set('showPayment', false)" x-data x-on:keydown.escape.window="$wire.set('showPayment', false)">
                <div class="pos-modal">
                    <h3>Payment <span class="pos-ha">· Biyan Kuɗi</span> — <span class="pos-total">₦{{ number_format($cartTotal) }}</span></h3>

                    @if (! empty($chargeLines))
                        <div class="pos-zsummary" style="margin-bottom: .8rem;">
                            <div><span>Subtotal</span><strong>₦{{ number_format($cartSubtotal) }}</strong></div>
                            @foreach ($chargeLines as $chargeLine)
                                <div><span>{{ $chargeLine['name'] }}</span><strong>+₦{{ number_format($chargeLine['amount']) }}</strong></div>
                            @endforeach
                        </div>
                    @endif

                    <div class="pos-pay-methods">
                        <button type="button" wire:click="$set('paymentMethod', 'cash')" onclick="posSound('tap')" class="pos-pay-method {{ $paymentMethod === 'cash' ? 'selected' : '' }}">
                            <x-filament::icon icon="heroicon-o-banknotes" class="pos-pay-icon" />
                            Cash<br><small>Tsabar Kuɗi</small>
                        </button>
                        <button type="button" wire:click="$set('paymentMethod', 'transfer')" onclick="posSound('tap')" class="pos-pay-method {{ $paymentMethod === 'transfer' ? 'selected' : '' }}">
                            <x-filament::icon icon="heroicon-o-building-library" class="pos-pay-icon" />
                            Transfer
                        </button>
                        <button type="button" wire:click="$set('paymentMethod', 'pos')" onclick="posSound('tap')" class="pos-pay-method {{ $paymentMethod === 'pos' ? 'selected' : '' }}">
                            <x-filament::icon icon="heroicon-o-credit-card" class="pos-pay-icon" />
                            POS Card
                        </button>
                    </div>

                    @if ($paymentMethod === 'cash')
                        <label>Amount received (₦) <span class="pos-ha">· Kuɗin da aka karɓa</span></label>
                        <input type="number" wire:model.live.debounce.400ms="amountPaid" min="0" placeholder="Shigar da kuɗin da aka baka" />
                        @error('amountPaid')
                            <div class="pos-error">
                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="pos-icon" />
                                {{ $message }}
                            </div>
                        @enderror

                        @php
                            // Quick-tender buttons: exact amount plus the next round notes above the total.
                            $tenders = collect([500, 1000, 2000, 5000, 10000])
                                ->map(fn ($n) => (int) (ceil($cartTotal / $n) * $n))
                                ->prepend((int) $cartTotal)
                                ->unique()
                                ->filter(fn ($v) => $v >= $cartTotal)
                                ->sort()
                                ->take(4)
                                ->values();
                        @endphp
                        <div class="pos-quick-cash">
                            @foreach ($tenders as $i => $tender)
                                <button type="button" wire:click="$set('amountPaid', '{{ $tender }}')" onclick="posSound('tap')"
                                    class="pos-quick-btn {{ (float) $amountPaid === (float) $tender ? 'selected' : '' }}">
                                    {{ $i === 0 ? 'Exact · Daidai' : '₦'.number_format($tender) }}
                                </button>
                            @endforeach
                        </div>

                        @if (filled($amountPaid) && (float) $amountPaid >= $cartTotal)
                            <div class="pos-change" wire:key="change-{{ $amountPaid }}">
                                <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="pos-icon" />
                                Change due <span class="pos-ha">· Canji</span>: <strong>₦{{ number_format((float) $amountPaid - $cartTotal) }}</strong>
                            </div>
                        @elseif (filled($amountPaid) && (float) $amountPaid < $cartTotal)
                            <div class="pos-error">
                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="pos-icon" />
                                ₦{{ number_format($cartTotal - (float) $amountPaid) }} short · Kuɗin bai kai ba
                            </div>
                        @endif
                    @else
                        <label>{{ $paymentMethod === 'transfer' ? 'Transfer reference / sender name' : 'POS terminal reference' }}</label>
                        <input type="text" wire:model="paymentReference" placeholder="Required — for bank reconciliation" />
                        @error('paymentReference')
                            <div class="pos-error">
                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="pos-icon" />
                                {{ $message }}
                            </div>
                        @enderror
                    @endif

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="$set('showPayment', false)" class="pos-btn-ghost">Cancel</button>
                        <button type="button" wire:click="completeSale" wire:loading.attr="disabled" wire:target="completeSale" class="pos-btn-primary">
                            <span wire:loading.remove wire:target="completeSale" class="pos-btn-inner">
                                <x-filament::icon icon="heroicon-m-check-circle" class="pos-icon" />
                                Complete & Print · Kammala a Buga Rasidi
                            </span>
                            <span wire:loading wire:target="completeSale" class="pos-btn-inner"><span class="pos-spinner"></span> Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== CLOSE SHIFT MODAL ===================== --}}
        @if ($showCloseShift)
            @php $expectedCash = (float) $shift->opening_float + $shift->cashSalesTotal(); @endphp
            <div class="pos-overlay" wire:click.self="$set('showCloseShift', false)" x-data x-on:keydown.escape.window="$wire.set('showCloseShift', false)">
                <div class="pos-modal">
                    <h3>
                        <x-filament::icon icon="heroicon-o-lock-closed" class="pos-icon" />
                        Close Shift — Z-Report <span class="pos-ha">· Rufe Aiki</span>
                    </h3>
                    <div class="pos-zsummary">
                        <div><span>Opening float <span class="pos-ha">· Kuɗin fara aiki</span></span><strong>₦{{ number_format((float) $shift->opening_float) }}</strong></div>
                        <div><span>Cash sales <span class="pos-ha">· Sayarwar tsaba</span></span><strong>₦{{ number_format($shift->cashSalesTotal()) }}</strong></div>
                        <div><span>Transfers</span><strong>₦{{ number_format($shift->salesTotalByMethod('transfer')) }}</strong></div>
                        <div><span>POS card</span><strong>₦{{ number_format($shift->salesTotalByMethod('pos')) }}</strong></div>
                        <div class="pos-zexpected"><span>Cash expected in drawer <span class="pos-ha">· Kuɗin da ya kamata a samu</span></span><strong>₦{{ number_format($expectedCash) }}</strong></div>
                    </div>

                    <label>Cash physically counted (₦) <span class="pos-ha">· Kuɗin da ka ƙidaya a akwati</span></label>
                    <input type="number" wire:model.live.debounce.400ms="countedCash" min="0" placeholder="Ƙidaya kuɗin akwati ka shigar da adadin" />
                    @error('countedCash')
                        <div class="pos-error">
                            <x-filament::icon icon="heroicon-m-exclamation-circle" class="pos-icon" />
                            {{ $message }}
                        </div>
                    @enderror

                    @if (filled($countedCash))
                        @php $variance = (float) $countedCash - $expectedCash; @endphp
                        <div class="pos-variance {{ $variance < 0 ? 'short' : ($variance > 0 ? 'over' : 'ok') }}" wire:key="variance-{{ $countedCash }}">
                            @if ($variance === 0.0)
                                <x-filament::icon icon="heroicon-m-check-circle" class="pos-icon" />
                                Drawer balances exactly · Kuɗi sun yi daidai
                            @elseif ($variance < 0)
                                <x-filament::icon icon="heroicon-m-arrow-trending-down" class="pos-icon" />
                                ₦{{ number_format(abs($variance)) }} short · Kuɗi sun ragu
                            @else
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="pos-icon" />
                                ₦{{ number_format($variance) }} over · Kuɗi sun ƙaru
                            @endif
                        </div>
                    @endif

                    <label>Notes (optional) <span class="pos-ha">· Bayani</span></label>
                    <input type="text" wire:model="closeNotes" placeholder="Any remarks…" />

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="$set('showCloseShift', false)" class="pos-btn-ghost">Cancel · Soke</button>
                        <button type="button" wire:click="closeShift" wire:loading.attr="disabled" wire:target="closeShift" class="pos-btn-danger">
                            <span wire:loading.remove wire:target="closeShift" class="pos-btn-inner">
                                <x-filament::icon icon="heroicon-m-printer" class="pos-icon" />
                                Close Shift & Print Z-Report · Rufe Aiki
                            </span>
                            <span wire:loading wire:target="closeShift" class="pos-btn-inner"><span class="pos-spinner"></span> Closing…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ===================== STYLES ===================== --}}
    <style>
        [x-cloak] { display: none !important; }
        .pos-icon { width: 1.05em; height: 1.05em; display: inline-block; vertical-align: -0.15em; flex-shrink: 0; }
        .pos-icon-lg { width: 1.5em; height: 1.5em; }
        .pos-btn-inner { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; }

        .pos-spinner { width: 1em; height: 1em; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; display: inline-block; animation: pos-spin .7s linear infinite; }
        @keyframes pos-spin { to { transform: rotate(360deg); } }

        .pos-open-shift { display: flex; justify-content: center; padding: 3rem 1rem; }
        .pos-open-card { max-width: 420px; width: 100%; background: var(--pos-card, #fff); border: 1px solid rgba(120,120,120,.2); border-radius: 1rem; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.08); animation: pos-pop .25s ease; }
        .dark .pos-open-card { background: #18181b; }
        .pos-open-icon { display: flex; justify-content: center; margin-bottom: .75rem; }
        .pos-open-icon svg { width: 3rem; height: 3rem; color: #d97706; }
        .dark .pos-open-icon svg { color: #fbbf24; }
        .pos-open-card h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .5rem; }
        .pos-open-card p { opacity: .7; font-size: .9rem; margin-bottom: 1.25rem; }
        .pos-open-card label { display: block; text-align: left; font-size: .85rem; font-weight: 600; margin-bottom: .35rem; }
        .pos-open-card input { width: 100%; margin-bottom: 1rem; }

        .pos-ha { opacity: .62; font-weight: 500; font-size: .88em; font-style: italic; }
        .pos-ha-note { opacity: .62; font-size: .8rem; font-style: italic; margin-top: -0.75rem; margin-bottom: 1.25rem !important; }

        .pos-stale-shift { display: flex; gap: .7rem; align-items: flex-start; background: rgba(220, 38, 38, .1); border: 1.5px solid #dc2626; color: #b91c1c; border-radius: .8rem; padding: .9rem 1.1rem; font-size: .9rem; margin-bottom: 1rem; }
        .dark .pos-stale-shift { color: #fca5a5; }

        .pos-wrap { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; align-items: start; }
        @media (max-width: 1024px) { .pos-wrap { grid-template-columns: 1fr; } }

        .pos-searchbar input, .pos-modal input, .pos-open-card input {
            width: 100%; padding: .7rem .9rem; border-radius: .6rem; border: 1px solid rgba(120,120,120,.3);
            background: transparent; font-size: .95rem; color: inherit; transition: border-color .12s ease, box-shadow .12s ease;
        }
        .pos-searchbar input:focus, .pos-modal input:focus, .pos-open-card input:focus {
            outline: none; border-color: #d97706; box-shadow: 0 0 0 3px rgba(217,119,6,.15);
        }
        .pos-searchbar { position: relative; margin-bottom: .75rem; }
        .pos-searchbar input { padding-left: 2.4rem; }
        .pos-search-icon { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.15rem; height: 1.15rem; opacity: .5; pointer-events: none; }
        .pos-search-clear { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); background: rgba(120,120,120,.15); border: none; border-radius: 999px; width: 1.6rem; height: 1.6rem; display: flex; align-items: center; justify-content: center; cursor: pointer; color: inherit; }
        .pos-search-clear:hover { background: rgba(120,120,120,.3); }

        .pos-tabs { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .pos-tab { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem 1rem; border-radius: 999px; border: 1px solid rgba(120,120,120,.3); font-size: .85rem; font-weight: 600; cursor: pointer; background: transparent; color: inherit; transition: transform .08s ease, background .12s ease, border-color .12s ease; }
        .pos-tab:hover { border-color: var(--tab-color, #d97706); transform: translateY(-1px); }
        .pos-tab:active { transform: scale(.96); }
        .pos-tab.active { background: var(--tab-color, #f59e0b); border-color: var(--tab-color, #f59e0b); color: #fff; }

        .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: .8rem; }
        .pos-item { position: relative; display: flex; flex-direction: column; padding: 0; border-radius: .9rem; overflow: hidden;
            border: 1px solid rgba(120,120,120,.25); border-bottom: 4px solid var(--item-color, #f59e0b);
            background: var(--pos-card, #fff); cursor: pointer; text-align: left; transition: transform .08s ease, box-shadow .08s ease; color: inherit; }
        .dark .pos-item { background: #18181b; }
        .pos-item:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(0,0,0,.14); }
        .pos-item:hover .pos-item-img img { transform: scale(1.06); }
        .pos-item:active { transform: scale(.96); }

        .pos-item-img { position: relative; display: block; width: 100%; aspect-ratio: 4 / 3; background: color-mix(in srgb, var(--item-color, #f59e0b) 16%, transparent); overflow: hidden; }
        .pos-item-img img { width: 100% !important; max-width: none !important; height: 100% !important; object-fit: cover; display: block; transition: transform .18s ease; }
        .pos-item-img-fallback { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; font-weight: 800; color: var(--item-color, #f59e0b); opacity: .8; }
        .pos-item-section { position: absolute; top: .45rem; left: .45rem; font-size: .6rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 800; background: rgba(0,0,0,.55); color: #fff; padding: .18rem .5rem; border-radius: 999px; backdrop-filter: blur(2px); }
        .pos-item-badge { position: absolute; top: .4rem; right: .4rem; min-width: 1.5rem; height: 1.5rem; padding: 0 .35rem; border-radius: 999px; background: #16a34a; color: #fff; font-size: .78rem; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,.35); animation: pos-pop .18s ease; }

        .pos-item-body { display: flex; flex-direction: column; gap: .25rem; padding: .6rem .75rem .7rem; }
        .pos-item-name { font-weight: 700; font-size: .92rem; line-height: 1.25; }
        .pos-item-meta { display: flex; justify-content: space-between; align-items: center; gap: .4rem; }
        .pos-item-price { color: #d97706; font-weight: 800; font-size: .98rem; }
        .dark .pos-item-price { color: #fbbf24; }
        .pos-item-opts { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; opacity: .6; }

        .pos-no-results { text-align: center; padding: 3rem 1rem; opacity: .7; }
        .pos-no-results svg { width: 2.5rem; height: 2.5rem; opacity: .4; margin-bottom: .5rem; }
        .pos-no-results p { margin-bottom: .3rem; font-weight: 600; }
        .pos-btn-inline { width: auto !important; display: inline-block; margin-top: .8rem; }

        .pos-cart { background: var(--pos-card, #fff); border: 1px solid rgba(120,120,120,.25); border-radius: 1rem; display: flex; flex-direction: column; min-height: 480px; max-height: calc(100vh - 180px); position: sticky; top: 1rem; }
        .dark .pos-cart { background: #18181b; }
        .pos-cart-head { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px dashed rgba(120,120,120,.3); }
        .pos-table-banner { display: flex; align-items: center; gap: .4rem; background: rgba(217,119,6,.12); color: #b45309; font-size: .78rem; font-weight: 600; padding: .55rem 1rem; }
        .dark .pos-table-banner { color: #fbbf24; }
        .pos-cart-title { display: inline-flex; align-items: center; gap: .4rem; }
        .pos-count-badge { min-width: 1.4rem; height: 1.4rem; padding: 0 .3rem; border-radius: 999px; background: #d97706; color: #fff; font-size: .75rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; animation: pos-pop .18s ease; }
        .pos-shift-badge { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; opacity: .6; margin-top: .15rem; }
        .pos-clear { display: inline-flex; align-items: center; gap: .3rem; color: #ef4444; font-size: .8rem; font-weight: 600; background: none; border: none; cursor: pointer; padding: .3rem .5rem; border-radius: .4rem; }
        .pos-clear:hover { background: rgba(239,68,68,.1); }

        .pos-cart-lines { flex: 1; overflow-y: auto; padding: .5rem 1rem; }
        .pos-line { display: grid; grid-template-columns: 1fr auto auto auto; gap: .6rem; align-items: center; padding: .6rem 0; border-bottom: 1px solid rgba(120,120,120,.12); animation: pos-slide-in .18s ease; }
        @keyframes pos-slide-in { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: none; } }
        .pos-line-name { font-weight: 600; font-size: .88rem; display: block; }
        .pos-line-opts { font-size: .72rem; color: #d97706; display: block; }
        .pos-line-unit { font-size: .72rem; opacity: .55; }
        .pos-line-qty { display: flex; align-items: center; gap: .4rem; }
        .pos-line-qty button { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(120,120,120,.35); background: transparent; font-weight: 700; cursor: pointer; color: inherit; line-height: 1; display: flex; align-items: center; justify-content: center; transition: background .1s ease, transform .08s ease; }
        .pos-line-qty button:hover { background: rgba(217,119,6,.12); border-color: #d97706; }
        .pos-line-qty button:active { transform: scale(.88); }
        .pos-line-qty span { min-width: 20px; text-align: center; font-weight: 700; animation: pos-pop .18s ease; }
        .pos-line-total { font-weight: 700; font-size: .9rem; white-space: nowrap; }
        .pos-line-remove { color: #ef4444; background: none; border: none; cursor: pointer; padding: .3rem; border-radius: .35rem; display: flex; }
        .pos-line-remove:hover { background: rgba(239,68,68,.12); }
        .pos-cart-empty { text-align: center; padding: 3rem 1rem; opacity: .5; }
        .pos-cart-empty svg { width: 2.8rem; height: 2.8rem; margin: 0 auto .5rem; }

        .pos-cart-foot { padding: 1rem; border-top: 1px dashed rgba(120,120,120,.3); }
        .pos-sub-row { display: flex; justify-content: space-between; font-size: .85rem; padding: .12rem 0; opacity: .8; }
        .pos-sub-charge span:last-child { font-weight: 700; color: #d97706; }
        .pos-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: .8rem; font-weight: 700; }
        .pos-total { font-size: 1.5rem; font-weight: 800; color: #d97706; display: inline-block; animation: pos-pop .18s ease; }
        .dark .pos-total { color: #fbbf24; }
        @keyframes pos-pop { from { transform: scale(1.18); } to { transform: scale(1); } }

        .pos-btn-primary { width: 100%; padding: .8rem 1.2rem; border-radius: .7rem; background: #d97706; color: #fff; font-weight: 700; font-size: 1rem; border: none; cursor: pointer; transition: background .12s ease, transform .08s ease; }
        .pos-btn-primary:hover { background: #b45309; }
        .pos-btn-primary:active { transform: scale(.98); }
        .pos-btn-primary:disabled { opacity: .4; cursor: not-allowed; transform: none; }
        .pos-btn-pay { margin-bottom: .5rem; }
        .pos-btn-ghost { width: 100%; padding: .6rem 1rem; border-radius: .7rem; background: transparent; border: 1px solid rgba(120,120,120,.35); font-weight: 600; font-size: .85rem; cursor: pointer; color: inherit; }
        .pos-btn-ghost:hover { background: rgba(120,120,120,.1); }
        .pos-btn-close-day { width: 100%; margin-top: .65rem; padding: .75rem 1rem; border-radius: .7rem; background: #b91c1c; border: none; color: #fff; font-weight: 800; font-size: .9rem; cursor: pointer; transition: background .12s ease; }
        .pos-btn-close-day:hover { background: #991b1b; }
        .pos-btn-danger { width: 100%; padding: .8rem 1.2rem; border-radius: .7rem; background: #dc2626; color: #fff; font-weight: 700; border: none; cursor: pointer; }
        .pos-btn-danger:hover { background: #b91c1c; }
        .pos-btn-danger:disabled { opacity: .5; cursor: not-allowed; }

        .pos-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; animation: pos-fade .15s ease; }
        @keyframes pos-fade { from { opacity: 0; } to { opacity: 1; } }
        .pos-modal { background: #fff; color: #111; border-radius: 1rem; padding: 1.5rem; width: 100%; max-width: 460px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.35); animation: pos-modal-in .2s ease; }
        @keyframes pos-modal-in { from { opacity: 0; transform: translateY(14px) scale(.97); } to { opacity: 1; transform: none; } }
        .dark .pos-modal { background: #1f1f23; color: #f4f4f5; }
        .pos-modal-img { width: calc(100% + 3rem) !important; max-width: none !important; height: 180px !important; margin: -1.5rem -1.5rem 1rem; object-fit: cover; display: block; border-radius: 1rem 1rem 0 0; }
        .pos-modal h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; }
        .pos-modal h3 small { font-weight: 500; opacity: .6; font-size: .85rem; }
        .pos-modal label { display: block; font-size: .85rem; font-weight: 600; margin: .8rem 0 .3rem; }

        .pos-opt-group { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; opacity: .55; font-weight: 700; margin: .8rem 0 .4rem; }
        .pos-opt-list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .pos-opt { display: inline-flex; align-items: center; gap: .3rem; padding: .5rem .85rem; border-radius: .6rem; border: 1.5px solid rgba(120,120,120,.35); background: transparent; cursor: pointer; font-size: .85rem; font-weight: 600; color: inherit; transition: border-color .1s ease, background .1s ease, transform .08s ease; }
        .pos-opt:active { transform: scale(.95); }
        .pos-opt span { opacity: .65; font-size: .78rem; }
        .pos-opt.selected { border-color: #d97706; background: rgba(217,119,6,.12); color: #d97706; }
        .dark .pos-opt.selected { color: #fbbf24; border-color: #fbbf24; }

        .pos-modal-qty { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; font-weight: 600; font-size: .9rem; }
        .pos-modal-actions { display: flex; gap: .6rem; margin-top: 1.25rem; }
        .pos-modal-actions .pos-btn-ghost { width: 40%; }

        .pos-pay-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: .5rem; }
        .pos-pay-method { padding: .8rem .5rem; border-radius: .7rem; border: 1.5px solid rgba(120,120,120,.35); background: transparent; font-weight: 700; font-size: .85rem; cursor: pointer; color: inherit; transition: border-color .1s ease, background .1s ease, transform .08s ease; }
        .pos-pay-method:active { transform: scale(.96); }
        .pos-pay-icon { width: 1.5rem; height: 1.5rem; display: block; margin: 0 auto .3rem; }
        .pos-pay-method.selected { border-color: #d97706; background: rgba(217,119,6,.12); color: #d97706; }
        .dark .pos-pay-method.selected { color: #fbbf24; border-color: #fbbf24; }

        .pos-quick-cash { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .6rem; }
        .pos-quick-btn { padding: .45rem .8rem; border-radius: .55rem; border: 1.5px solid rgba(120,120,120,.35); background: transparent; font-weight: 700; font-size: .85rem; cursor: pointer; color: inherit; transition: border-color .1s ease, background .1s ease; }
        .pos-quick-btn:hover, .pos-quick-btn.selected { border-color: #16a34a; background: rgba(22,163,74,.12); color: #16a34a; }
        .dark .pos-quick-btn:hover, .dark .pos-quick-btn.selected { color: #4ade80; border-color: #4ade80; }

        .pos-change { display: flex; align-items: center; gap: .4rem; margin-top: .6rem; padding: .6rem .8rem; background: rgba(22,163,74,.12); color: #15803d; border-radius: .6rem; font-size: .95rem; font-weight: 600; animation: pos-pop .18s ease; }
        .dark .pos-change { color: #4ade80; }
        .pos-error { display: flex; align-items: center; gap: .35rem; color: #ef4444; font-size: .85rem; font-weight: 600; margin-top: .4rem; }

        .pos-variance { display: flex; align-items: center; gap: .4rem; margin-top: .5rem; padding: .55rem .8rem; border-radius: .6rem; font-size: .9rem; font-weight: 700; animation: pos-pop .18s ease; }
        .pos-variance.ok { background: rgba(22,163,74,.12); color: #15803d; }
        .dark .pos-variance.ok { color: #4ade80; }
        .pos-variance.short { background: rgba(220,38,38,.12); color: #b91c1c; }
        .dark .pos-variance.short { color: #fca5a5; }
        .pos-variance.over { background: rgba(217,119,6,.12); color: #b45309; }
        .dark .pos-variance.over { color: #fbbf24; }

        .pos-zsummary { border: 1px dashed rgba(120,120,120,.4); border-radius: .7rem; padding: .8rem 1rem; margin-bottom: .5rem; }
        .pos-zsummary > div { display: flex; justify-content: space-between; padding: .25rem 0; font-size: .9rem; }
        .pos-zexpected { border-top: 1px dashed rgba(120,120,120,.4); margin-top: .4rem; padding-top: .5rem !important; font-size: 1rem !important; }
    </style>

    <script>
        // POS sound effects — generated with Web Audio API: no files, instant, works offline.
        let posAudioCtx = null;

        function posTone(freq, start, duration, type = 'sine', volume = 0.22) {
            const osc = posAudioCtx.createOscillator();
            const gain = posAudioCtx.createGain();
            osc.type = type;
            osc.frequency.value = freq;
            const t = posAudioCtx.currentTime + start;
            gain.gain.setValueAtTime(volume, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + duration);
            osc.connect(gain).connect(posAudioCtx.destination);
            osc.start(t);
            osc.stop(t + duration);
        }

        function posSound(kind) {
            try {
                posAudioCtx = posAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
                if (posAudioCtx.state === 'suspended') posAudioCtx.resume();

                switch (kind) {
                    case 'tap':   // product tapped / button pressed
                        posTone(880, 0, 0.07);
                        break;
                    case 'add':   // item added to cart (with options)
                        posTone(740, 0, 0.06);
                        posTone(1108, 0.06, 0.10);
                        break;
                    case 'up':    // quantity +
                        posTone(660, 0, 0.05);
                        posTone(880, 0.05, 0.08);
                        break;
                    case 'down':  // quantity − / remove
                        posTone(880, 0, 0.05);
                        posTone(587, 0.05, 0.09);
                        break;
                    case 'cash':  // payment completed — cash register "cha-ching"
                        posTone(1046, 0.00, 0.09, 'triangle', 0.3);
                        posTone(1318, 0.08, 0.09, 'triangle', 0.3);
                        posTone(1568, 0.16, 0.11, 'triangle', 0.3);
                        posTone(2093, 0.24, 0.30, 'triangle', 0.32);
                        break;
                    case 'error': // problem — low double buzz
                        posTone(196, 0, 0.13, 'square', 0.12);
                        posTone(196, 0.17, 0.13, 'square', 0.12);
                        break;
                }
            } catch (e) { /* audio unavailable — never block a sale over a beep */ }
        }

        document.addEventListener('livewire:init', () => {
            Livewire.on('open-receipt', ({ url }) => {
                posSound('cash');
                window.open(url, '_blank', 'width=420,height=650');
            });
            // Stay on the POS after a sale — the receipt prints from a popup
            // and the cashier is immediately ready for the next customer.
            Livewire.on('sale-completed', ({ receiptUrl }) => {
                posSound('cash');
                window.open(receiptUrl, '_blank', 'width=420,height=650');
            });
            Livewire.on('pos-error', () => posSound('error'));
        });
    </script>
</x-filament-panels::page>
