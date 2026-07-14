<x-filament-panels::page>
    <div wire:poll.10s>
        @if ($tables->isEmpty())
            <div class="to-empty">
                <x-filament::icon icon="heroicon-o-qr-code" />
                <p>No active tables yet.</p>
                <p class="to-ha">Add tables under Tables to generate their QR codes.</p>
            </div>
        @else
            <div class="to-grid">
                @foreach ($tables as $table)
                    @php
                        $openTotal = $table->openTabTotal();
                    @endphp
                    <div class="to-card {{ $table->pending_count > 0 ? 'to-card-alert' : '' }}">
                        <div class="to-card-head">
                            <strong>{{ $table->name }}</strong>
                            <div class="to-card-actions">
                                @if ($openTotal > 0)
                                    <span class="to-tab-badge" title="Accepted, not yet paid">₦{{ number_format($openTotal) }} open</span>
                                @endif
                                @if ($table->pending_count > 0)
                                    <span class="to-pending-badge">{{ $table->pending_count }} new</span>
                                @endif
                            </div>
                        </div>

                        @forelse ($table->customerOrders as $order)
                            <div class="to-order" wire:key="order-{{ $order->id }}">
                                <div class="to-order-head">
                                    <span>
                                        @if ($order->customer_name)
                                            {{ $order->customer_name }} ·
                                        @endif
                                        {{ $order->created_at->diffForHumans() }}
                                    </span>
                                    <strong>₦{{ number_format($order->total()) }}</strong>
                                </div>
                                <ul class="to-order-items">
                                    @foreach ($order->items as $item)
                                        <li>
                                            {{ $item->quantity }}x {{ $item->product_name }}
                                            @if (! empty($item->options))
                                                <span class="to-ha">({{ collect($item->options)->pluck('name')->join(', ') }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($order->note)
                                    <div class="to-order-note">
                                        <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="to-icon" />
                                        {{ $order->note }}
                                    </div>
                                @endif

                                @if ($rejectingOrderId === $order->id)
                                    <div class="to-reject-box">
                                        <input type="text" wire:model="rejectReason" placeholder="Reason — e.g. item out of stock" autofocus />
                                        @error('rejectReason') <div class="to-error">{{ $message }}</div> @enderror
                                        <div class="to-order-actions">
                                            <button type="button" wire:click="cancelReject" class="to-btn-ghost">Cancel</button>
                                            <button type="button" wire:click="confirmReject" class="to-btn-danger">Confirm Reject</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="to-order-actions">
                                        <button type="button" wire:click="startReject({{ $order->id }})" class="to-btn-ghost">
                                            <x-filament::icon icon="heroicon-m-x-mark" class="to-icon" /> Reject
                                        </button>
                                        <button type="button" wire:click="accept({{ $order->id }})" wire:loading.attr="disabled" class="to-btn-primary">
                                            <x-filament::icon icon="heroicon-m-check" class="to-icon" /> Accept & Send to Kitchen
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="to-none">No pending orders right now.</p>
                        @endforelse

                        @if ($openTotal > 0)
                            <a href="{{ \App\Filament\Pages\PointOfSale::getUrl(['chargeTable' => $table->id]) }}" class="to-charge-link">
                                <x-filament::icon icon="heroicon-m-banknotes" class="to-icon" />
                                Charge this table — ₦{{ number_format($openTotal) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .to-empty { text-align: center; padding: 4rem 1rem; opacity: .6; }
        .to-empty svg { width: 3rem; height: 3rem; margin: 0 auto .6rem; }
        .to-ha { opacity: .6; font-style: italic; font-size: .85em; }
        .to-icon { width: 1em; height: 1em; display: inline-block; vertical-align: -0.15em; }

        .to-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
        .to-card { background: #fff; border: 1px solid rgba(120,120,120,.2); border-radius: .9rem; padding: 1rem; }
        .dark .to-card { background: #221f19; }
        .to-card-alert { border-color: #d97706; box-shadow: 0 0 0 2px rgba(217,119,6,.15); }
        .to-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .6rem; }
        .to-card-head strong { font-size: 1.05rem; }
        .to-card-actions { display: flex; gap: .4rem; }
        .to-pending-badge { background: #d97706; color: #fff; font-size: .72rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; }
        .to-tab-badge { background: rgba(22,163,74,.15); color: #15803d; font-size: .72rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; }

        .to-order { border-top: 1px dashed rgba(120,120,120,.25); padding: .7rem 0; }
        .to-order-head { display: flex; justify-content: space-between; font-size: .82rem; font-weight: 700; margin-bottom: .3rem; }
        .to-order-items { margin: 0 0 .4rem; padding-left: 1.1rem; font-size: .85rem; }
        .to-order-note { display: flex; align-items: center; gap: .3rem; font-size: .78rem; background: rgba(217,119,6,.1); color: #b45309; padding: .35rem .6rem; border-radius: .5rem; margin-bottom: .5rem; }
        .to-order-actions { display: flex; gap: .5rem; margin-top: .5rem; }
        .to-btn-primary { flex: 1; padding: .55rem .8rem; border-radius: .6rem; background: #d97706; color: #fff; font-weight: 700; font-size: .8rem; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; }
        .to-btn-primary:hover { background: #b45309; }
        .to-btn-ghost { padding: .55rem .8rem; border-radius: .6rem; background: transparent; border: 1px solid rgba(120,120,120,.3); font-weight: 600; font-size: .8rem; cursor: pointer; color: inherit; display: inline-flex; align-items: center; gap: .3rem; }
        .to-btn-danger { flex: 1; padding: .55rem .8rem; border-radius: .6rem; background: #dc2626; color: #fff; font-weight: 700; font-size: .8rem; border: none; cursor: pointer; }
        .to-reject-box input { width: 100%; padding: .55rem .7rem; border-radius: .5rem; border: 1px solid rgba(120,120,120,.3); background: transparent; color: inherit; font-size: .85rem; margin-bottom: .4rem; }
        .to-error { color: #ef4444; font-size: .75rem; margin-bottom: .4rem; }
        .to-none { text-align: center; opacity: .5; font-size: .85rem; padding: 1rem 0; }

        .to-charge-link { display: flex; align-items: center; justify-content: center; gap: .4rem; margin-top: .8rem; padding: .7rem; border-radius: .6rem; background: rgba(22,163,74,.12); color: #15803d; font-weight: 700; font-size: .85rem; text-decoration: none; }
        .to-charge-link:hover { background: rgba(22,163,74,.2); }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-ticket', ({ url }) => {
                window.open(url, '_blank', 'width=420,height=650');
            });
        });
    </script>
</x-filament-panels::page>
