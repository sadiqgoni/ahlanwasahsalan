<x-filament-panels::page>
    @php
        $cartTotal = $this->cartTotal;
    @endphp

    @if (! $shift)
        {{-- ===================== OPEN SHIFT ===================== --}}
        <div class="pos-open-shift">
            <div class="pos-open-card">
                <div class="pos-open-emoji">🔓</div>
                <h2>Open Your Shift <small class="pos-ha">· Buɗe Aikinka</small></h2>
                <p>Enter the cash float you are starting with. Every sale you record will be tied to this shift — at closing, the cash in the drawer must match the system.</p>
                <p class="pos-ha-note">Shigar da kuɗin da ka fara aiki da su. Duk sayarwar da ka yi za a lissafa a kanka — idan ka rufe aiki, kuɗin da ke akwati dole su zama daidai da na kwamfuta.</p>
                <label>Opening cash float (₦) <span class="pos-ha">· Kuɗin fara aiki</span></label>
                <input type="number" wire:model="openingFloat" min="0" step="100" placeholder="0" />
                <button type="button" wire:click="openShift" class="pos-btn-primary">Start Shift · Fara Aiki</button>
            </div>
        </div>
    @else
        @if ($shift->opened_at->lt(today()))
            {{-- Shift left open from a previous day — Z-report will mix days --}}
            <div class="pos-stale-shift">
                ⚠️ This shift was opened on <strong>{{ $shift->opened_at->format('D d M, h:i A') }}</strong> and was never closed.
                Close it now and print the Z-report, then open a fresh shift for today — otherwise today's sales will mix into that day's report.
                <br><em>An buɗe wannan aikin tun {{ $shift->opened_at->format('d/m') }} ba a rufe ba. Ka rufe shi yanzu, sannan ka buɗe sabon aiki na yau.</em>
            </div>
        @endif

        {{-- ===================== POS LAYOUT ===================== --}}
        <div class="pos-wrap">
            {{-- LEFT: menu --}}
            <div class="pos-menu">
                <div class="pos-searchbar">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        wire:keydown.enter="scanOrSearch"
                        placeholder="🔍 Search item / Nemo abinci…"
                        autocomplete="off"
                    />
                </div>

                <div class="pos-tabs">
                    <button
                        type="button"
                        wire:click="$set('activeCategory', null)"
                        class="pos-tab {{ $activeCategory === null ? 'active' : '' }}"
                    >All</button>
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            wire:click="$set('activeCategory', {{ $category->id }})"
                            class="pos-tab {{ $activeCategory === $category->id ? 'active' : '' }}"
                            style="--tab-color: {{ $category->color }}"
                        >{{ $category->name }}</button>
                    @endforeach
                </div>

                <div class="pos-grid">
                    @foreach ($categories as $category)
                        @if ($activeCategory === null || $activeCategory === $category->id)
                            @foreach ($category->products as $product)
                                @if ($search === '' || str_contains(strtolower($product->name), strtolower($search)))
                                    <button
                                        type="button"
                                        wire:click="selectProduct({{ $product->id }})"
                                        onclick="posSound('add')"
                                        class="pos-item"
                                        style="--item-color: {{ $category->color }}"
                                    >
                                        <span class="pos-item-section">{{ $category->name }}</span>
                                        <span class="pos-item-name">{{ $product->name }}</span>
                                        <span class="pos-item-price">₦{{ number_format((float) $product->price) }}</span>
                                        @if ($product->options->isNotEmpty())
                                            <span class="pos-item-opts">+ options</span>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- RIGHT: cart --}}
            <div class="pos-cart">
                <div class="pos-cart-head">
                    <div>
                        <strong>Current Order <span class="pos-ha">· Oda</span></strong>
                        <span class="pos-shift-badge">Shift open since {{ $shift->opened_at->format('h:i A') }}</span>
                    </div>
                    @if (! empty($cart))
                        <button type="button" wire:click="clearCart" class="pos-clear">Clear</button>
                    @endif
                </div>

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
                                <button type="button" wire:click="decrementLine('{{ $key }}')" onclick="posSound('down')">−</button>
                                <span>{{ $line['qty'] }}</span>
                                <button type="button" wire:click="incrementLine('{{ $key }}')" onclick="posSound('up')">+</button>
                            </div>
                            <div class="pos-line-total">₦{{ number_format($line['unit_price'] * $line['qty']) }}</div>
                            <button type="button" wire:click="removeLine('{{ $key }}')" onclick="posSound('down')" class="pos-line-remove">✕</button>
                        </div>
                    @empty
                        <div class="pos-cart-empty">
                            <div>🧾</div>
                            <p>Tap items to add them to the order</p>
                            <p class="pos-ha">Danna abinci don saka a cikin oda</p>
                        </div>
                    @endforelse
                </div>

                <div class="pos-cart-foot">
                    <div class="pos-total-row">
                        <span>TOTAL <span class="pos-ha">· JIMLA</span></span>
                        <span class="pos-total">₦{{ number_format($cartTotal) }}</span>
                    </div>
                    <button type="button" wire:click="startPayment" onclick="posSound('tap')" class="pos-btn-primary pos-btn-pay" @if(empty($cart)) disabled @endif>
                        💵 Take Payment · Karɓi Kuɗi
                    </button>
                    <button type="button" wire:click="$set('showCloseShift', true)" class="pos-btn-ghost">
                        Close Shift · Rufe Aiki / Z-Report
                    </button>
                </div>
            </div>
        </div>

        {{-- ===================== OPTIONS MODAL ===================== --}}
        @if ($modalProduct)
            <div class="pos-overlay" wire:click.self="closeModal">
                <div class="pos-modal">
                    <h3>{{ $modalProduct->name }} <small>₦{{ number_format((float) $modalProduct->price) }} base</small></h3>
                    @foreach ($modalProduct->options->where('is_active', true)->groupBy('group') as $group => $options)
                        <div class="pos-opt-group">{{ $group }}</div>
                        <div class="pos-opt-list">
                            @foreach ($options as $option)
                                <button
                                    type="button"
                                    wire:click="toggleOption({{ $option->id }})"
                                    class="pos-opt {{ in_array($option->id, $selectedOptions) ? 'selected' : '' }}"
                                >
                                    {{ $option->name }}
                                    <span>+₦{{ number_format((float) $option->price) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="pos-modal-qty">
                        <span>Quantity <span class="pos-ha">· Adadi</span></span>
                        <div class="pos-line-qty">
                            <button type="button" wire:click="$set('modalQty', {{ max(1, $modalQty - 1) }})">−</button>
                            <span>{{ $modalQty }}</span>
                            <button type="button" wire:click="$set('modalQty', {{ $modalQty + 1 }})">+</button>
                        </div>
                    </div>

                    @php
                        $optTotal = $modalProduct->options->whereIn('id', $selectedOptions)->sum('price');
                        $modalUnit = (float) $modalProduct->price + (float) $optTotal;
                    @endphp

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="closeModal" class="pos-btn-ghost">Cancel</button>
                        <button type="button" wire:click="confirmOptions" onclick="posSound('add')" class="pos-btn-primary">
                            Add — ₦{{ number_format($modalUnit * max(1, $modalQty)) }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== PAYMENT MODAL ===================== --}}
        @if ($showPayment)
            <div class="pos-overlay" wire:click.self="$set('showPayment', false)">
                <div class="pos-modal">
                    <h3>Payment <span class="pos-ha">· Biyan Kuɗi</span> — <span class="pos-total">₦{{ number_format($cartTotal) }}</span></h3>

                    <div class="pos-pay-methods">
                        <button type="button" wire:click="$set('paymentMethod', 'cash')" class="pos-pay-method {{ $paymentMethod === 'cash' ? 'selected' : '' }}">💵 Cash<br><small>Tsabar Kuɗi</small></button>
                        <button type="button" wire:click="$set('paymentMethod', 'transfer')" class="pos-pay-method {{ $paymentMethod === 'transfer' ? 'selected' : '' }}">🏦 Transfer</button>
                        <button type="button" wire:click="$set('paymentMethod', 'pos')" class="pos-pay-method {{ $paymentMethod === 'pos' ? 'selected' : '' }}">💳 POS Card</button>
                    </div>

                    @if ($paymentMethod === 'cash')
                        <label>Amount received (₦) <span class="pos-ha">· Kuɗin da aka karɓa</span></label>
                        <input type="number" wire:model.live="amountPaid" min="0" placeholder="{{ number_format($cartTotal) }}" />
                        @if (filled($amountPaid) && (float) $amountPaid >= $cartTotal)
                            <div class="pos-change">Change due <span class="pos-ha">· Canji</span>: <strong>₦{{ number_format((float) $amountPaid - $cartTotal) }}</strong></div>
                        @endif
                    @else
                        <label>{{ $paymentMethod === 'transfer' ? 'Transfer reference / sender name' : 'POS terminal reference' }}</label>
                        <input type="text" wire:model="paymentReference" placeholder="Required — for bank reconciliation" />
                        @error('paymentReference')
                            <div class="pos-error">{{ $message }}</div>
                        @enderror
                    @endif

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="$set('showPayment', false)" class="pos-btn-ghost">Cancel</button>
                        <button type="button" wire:click="completeSale" class="pos-btn-primary">
                            ✓ Complete & Print · Kammala a Buga Rasidi
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== CLOSE SHIFT MODAL ===================== --}}
        @if ($showCloseShift)
            <div class="pos-overlay" wire:click.self="$set('showCloseShift', false)">
                <div class="pos-modal">
                    <h3>Close Shift — Z-Report <span class="pos-ha">· Rufe Aiki</span></h3>
                    <div class="pos-zsummary">
                        <div><span>Opening float <span class="pos-ha">· Kuɗin fara aiki</span></span><strong>₦{{ number_format((float) $shift->opening_float) }}</strong></div>
                        <div><span>Cash sales <span class="pos-ha">· Sayarwar tsaba</span></span><strong>₦{{ number_format($shift->cashSalesTotal()) }}</strong></div>
                        <div><span>Transfers</span><strong>₦{{ number_format($shift->salesTotalByMethod('transfer')) }}</strong></div>
                        <div><span>POS card</span><strong>₦{{ number_format($shift->salesTotalByMethod('pos')) }}</strong></div>
                        <div class="pos-zexpected"><span>Cash expected in drawer <span class="pos-ha">· Kuɗin da ya kamata a samu</span></span><strong>₦{{ number_format((float) $shift->opening_float + $shift->cashSalesTotal()) }}</strong></div>
                    </div>

                    <label>Cash physically counted (₦) <span class="pos-ha">· Kuɗin da ka ƙidaya a akwati</span></label>
                    <input type="number" wire:model="countedCash" min="0" placeholder="Ƙidaya kuɗin akwati ka shigar da adadin" />
                    @error('countedCash')
                        <div class="pos-error">{{ $message }}</div>
                    @enderror

                    <label>Notes (optional) <span class="pos-ha">· Bayani</span></label>
                    <input type="text" wire:model="closeNotes" placeholder="Any remarks…" />

                    <div class="pos-modal-actions">
                        <button type="button" wire:click="$set('showCloseShift', false)" class="pos-btn-ghost">Cancel · Soke</button>
                        <button type="button" wire:click="closeShift" class="pos-btn-danger">Close Shift & Print Z-Report · Rufe Aiki</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ===================== STYLES ===================== --}}
    <style>
        .pos-open-shift { display: flex; justify-content: center; padding: 3rem 1rem; }
        .pos-open-card { max-width: 420px; width: 100%; background: var(--pos-card, #fff); border: 1px solid rgba(120,120,120,.2); border-radius: 1rem; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .dark .pos-open-card { background: #18181b; }
        .pos-open-emoji { font-size: 3rem; margin-bottom: .5rem; }
        .pos-open-card h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .5rem; }
        .pos-open-card p { opacity: .7; font-size: .9rem; margin-bottom: 1.25rem; }
        .pos-open-card label { display: block; text-align: left; font-size: .85rem; font-weight: 600; margin-bottom: .35rem; }
        .pos-open-card input { width: 100%; margin-bottom: 1rem; }

        .pos-ha { opacity: .62; font-weight: 500; font-size: .88em; font-style: italic; }
        .pos-ha-note { opacity: .62; font-size: .8rem; font-style: italic; margin-top: -0.75rem; margin-bottom: 1.25rem !important; }

        .pos-stale-shift { background: rgba(220, 38, 38, .1); border: 1.5px solid #dc2626; color: #b91c1c; border-radius: .8rem; padding: .9rem 1.1rem; font-size: .9rem; margin-bottom: 1rem; }
        .dark .pos-stale-shift { color: #fca5a5; }

        .pos-wrap { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; align-items: start; }
        @media (max-width: 1024px) { .pos-wrap { grid-template-columns: 1fr; } }

        .pos-searchbar input, .pos-modal input, .pos-open-card input {
            width: 100%; padding: .7rem .9rem; border-radius: .6rem; border: 1px solid rgba(120,120,120,.3);
            background: transparent; font-size: .95rem; color: inherit;
        }
        .pos-searchbar { margin-bottom: .75rem; }

        .pos-tabs { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .pos-tab { padding: .45rem 1rem; border-radius: 999px; border: 1px solid rgba(120,120,120,.3); font-size: .85rem; font-weight: 600; cursor: pointer; background: transparent; color: inherit; }
        .pos-tab.active { background: var(--tab-color, #f59e0b); border-color: var(--tab-color, #f59e0b); color: #fff; }

        .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
        .pos-item { position: relative; display: flex; flex-direction: column; gap: .2rem; padding: .85rem; border-radius: .8rem;
            border: 1px solid rgba(120,120,120,.25); border-top: 4px solid var(--item-color, #f59e0b);
            background: var(--pos-card, #fff); cursor: pointer; text-align: left; transition: transform .08s ease, box-shadow .08s ease; color: inherit; }
        .dark .pos-item { background: #18181b; }
        .pos-item:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.12); }
        .pos-item:active { transform: scale(.97); }
        .pos-item-section { font-size: .65rem; text-transform: uppercase; letter-spacing: .05em; opacity: .55; font-weight: 700; }
        .pos-item-name { font-weight: 700; font-size: .92rem; line-height: 1.25; }
        .pos-item-price { color: #2563eb; font-weight: 800; font-size: .95rem; }
        .dark .pos-item-price { color: #60a5fa; }
        .pos-item-opts { font-size: .7rem; opacity: .6; }

        .pos-cart { background: var(--pos-card, #fff); border: 1px solid rgba(120,120,120,.25); border-radius: 1rem; display: flex; flex-direction: column; min-height: 480px; max-height: calc(100vh - 180px); position: sticky; top: 1rem; }
        .dark .pos-cart { background: #18181b; }
        .pos-cart-head { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px dashed rgba(120,120,120,.3); }
        .pos-shift-badge { display: block; font-size: .7rem; opacity: .6; }
        .pos-clear { color: #ef4444; font-size: .8rem; font-weight: 600; background: none; border: none; cursor: pointer; }

        .pos-cart-lines { flex: 1; overflow-y: auto; padding: .5rem 1rem; }
        .pos-line { display: grid; grid-template-columns: 1fr auto auto auto; gap: .6rem; align-items: center; padding: .6rem 0; border-bottom: 1px solid rgba(120,120,120,.12); }
        .pos-line-name { font-weight: 600; font-size: .88rem; display: block; }
        .pos-line-opts { font-size: .72rem; color: #d97706; display: block; }
        .pos-line-unit { font-size: .72rem; opacity: .55; }
        .pos-line-qty { display: flex; align-items: center; gap: .4rem; }
        .pos-line-qty button { width: 26px; height: 26px; border-radius: 6px; border: 1px solid rgba(120,120,120,.35); background: transparent; font-weight: 700; cursor: pointer; color: inherit; line-height: 1; }
        .pos-line-qty span { min-width: 20px; text-align: center; font-weight: 700; }
        .pos-line-total { font-weight: 700; font-size: .9rem; white-space: nowrap; }
        .pos-line-remove { color: #ef4444; background: none; border: none; cursor: pointer; font-size: .8rem; }
        .pos-cart-empty { text-align: center; padding: 3rem 1rem; opacity: .5; }
        .pos-cart-empty div { font-size: 2.5rem; margin-bottom: .5rem; }

        .pos-cart-foot { padding: 1rem; border-top: 1px dashed rgba(120,120,120,.3); }
        .pos-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: .8rem; font-weight: 700; }
        .pos-total { font-size: 1.5rem; font-weight: 800; color: #2563eb; }
        .dark .pos-total { color: #60a5fa; }

        .pos-btn-primary { width: 100%; padding: .8rem 1.2rem; border-radius: .7rem; background: #2563eb; color: #fff; font-weight: 700; font-size: 1rem; border: none; cursor: pointer; }
        .pos-btn-primary:hover { background: #1d4ed8; }
        .pos-btn-primary:disabled { opacity: .4; cursor: not-allowed; }
        .pos-btn-pay { margin-bottom: .5rem; }
        .pos-btn-ghost { width: 100%; padding: .6rem 1rem; border-radius: .7rem; background: transparent; border: 1px solid rgba(120,120,120,.35); font-weight: 600; font-size: .85rem; cursor: pointer; color: inherit; }
        .pos-btn-danger { width: 100%; padding: .8rem 1.2rem; border-radius: .7rem; background: #dc2626; color: #fff; font-weight: 700; border: none; cursor: pointer; }

        .pos-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; }
        .pos-modal { background: #fff; color: #111; border-radius: 1rem; padding: 1.5rem; width: 100%; max-width: 460px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.35); }
        .dark .pos-modal { background: #1f1f23; color: #f4f4f5; }
        .pos-modal h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; }
        .pos-modal h3 small { font-weight: 500; opacity: .6; font-size: .85rem; }
        .pos-modal label { display: block; font-size: .85rem; font-weight: 600; margin: .8rem 0 .3rem; }

        .pos-opt-group { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; opacity: .55; font-weight: 700; margin: .8rem 0 .4rem; }
        .pos-opt-list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .pos-opt { padding: .5rem .85rem; border-radius: .6rem; border: 1.5px solid rgba(120,120,120,.35); background: transparent; cursor: pointer; font-size: .85rem; font-weight: 600; color: inherit; }
        .pos-opt span { opacity: .65; font-size: .78rem; }
        .pos-opt.selected { border-color: #2563eb; background: rgba(37,99,235,.12); color: #2563eb; }
        .dark .pos-opt.selected { color: #60a5fa; border-color: #60a5fa; }

        .pos-modal-qty { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; font-weight: 600; font-size: .9rem; }
        .pos-modal-actions { display: flex; gap: .6rem; margin-top: 1.25rem; }
        .pos-modal-actions .pos-btn-ghost { width: 40%; }

        .pos-pay-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: .5rem; }
        .pos-pay-method { padding: .8rem .5rem; border-radius: .7rem; border: 1.5px solid rgba(120,120,120,.35); background: transparent; font-weight: 700; font-size: .85rem; cursor: pointer; color: inherit; }
        .pos-pay-method.selected { border-color: #2563eb; background: rgba(37,99,235,.12); color: #2563eb; }
        .dark .pos-pay-method.selected { color: #60a5fa; border-color: #60a5fa; }
        .pos-change { margin-top: .6rem; padding: .6rem .8rem; background: rgba(37,99,235,.1); border-radius: .6rem; font-size: .9rem; }
        .pos-error { color: #ef4444; font-size: .8rem; margin-top: .3rem; }

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
            Livewire.on('pos-error', () => posSound('error'));
        });
    </script>
</x-filament-panels::page>
