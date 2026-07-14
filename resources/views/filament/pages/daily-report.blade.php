<x-filament-panels::page>
    @php
        $presets = [
            'today' => ['Today · Yau', now()->toDateString(), now()->toDateString()],
            'yesterday' => ['Yesterday · Jiya', now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'week' => ['This Week · Mako', now()->startOfWeek()->toDateString(), now()->toDateString()],
            'month' => ['This Month · Wata', now()->startOfMonth()->toDateString(), now()->toDateString()],
        ];
    @endphp

    <div class="dr-toolbar">
        <div class="dr-range">
            <div class="dr-presets">
                @foreach ($presets as $key => [$label, $presetFrom, $presetTo])
                    <button
                        type="button"
                        wire:click="setRange('{{ $key }}')"
                        class="dr-preset {{ $from === $presetFrom && $to === $presetTo ? 'active' : '' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>
            <div class="dr-datewrap">
                <label for="dr-from">From</label>
                <input id="dr-from" type="date" wire:model.live="from" max="{{ $to }}" />
            </div>
            <div class="dr-datewrap">
                <label for="dr-to">To</label>
                <input id="dr-to" type="date" wire:model.live="to" min="{{ $from }}" max="{{ now()->toDateString() }}" />
            </div>
        </div>
        <a href="{{ route('reports.print', ['from' => $from, 'to' => $to]) }}" target="_blank" class="dr-print">
            <x-filament::icon icon="heroicon-m-printer" class="dr-icon" />
            Print report
        </a>
    </div>

    <div wire:loading.delay class="dr-loading">
        <span class="dr-spinner"></span> Loading report · Ana lissafa…
    </div>

    {{-- Headline numbers --}}
    <div class="dr-stats">
        <div class="dr-stat dr-stat-main">
            <span>Total sales · Jimlar sayarwa</span>
            <strong>₦{{ number_format($report['total']) }}</strong>
            <small>{{ $report['receipts'] }} receipts · rasidi</small>
        </div>
        <div class="dr-stat">
            <span><x-filament::icon icon="heroicon-m-banknotes" class="dr-icon" /> Cash · Tsabar kuɗi</span>
            <strong>₦{{ number_format($report['byMethod']['cash']) }}</strong>
            <small>Must be in the drawer · Dole su kasance a akwati</small>
        </div>
        <div class="dr-stat">
            <span><x-filament::icon icon="heroicon-m-building-library" class="dr-icon" /> Transfer</span>
            <strong>₦{{ number_format($report['byMethod']['transfer']) }}</strong>
            <small>Match bank statement · A gwada da na banki</small>
        </div>
        <div class="dr-stat">
            <span><x-filament::icon icon="heroicon-m-credit-card" class="dr-icon" /> POS Card</span>
            <strong>₦{{ number_format($report['byMethod']['pos']) }}</strong>
            <small>Match terminal settlement · A gwada da na'ura</small>
        </div>
    </div>

    <div class="dr-grid">
        {{-- Cashier accountability --}}
        <div class="dr-card">
            <h3><x-filament::icon icon="heroicon-m-user" class="dr-icon" /> By Cashier <span class="dr-ha">· Kowane Ma'aikaci</span></h3>
            <table>
                <thead><tr><th>Cashier</th><th>Receipts</th><th>Cash</th><th>Total</th></tr></thead>
                <tbody>
                    @forelse ($report['byCashier'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['receipts'] }}</td>
                            <td>₦{{ number_format($row['cash']) }}</td>
                            <td class="dr-strong">₦{{ number_format($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dr-empty">No sales in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Shifts & variance --}}
        <div class="dr-card">
            <h3><x-filament::icon icon="heroicon-m-clock" class="dr-icon" /> Shifts &amp; Drawer Variance <span class="dr-ha">· Bambancin Kuɗin Akwati</span></h3>
            <table>
                <thead><tr><th>Cashier</th><th>Status</th><th>Expected</th><th>Counted</th><th>Variance</th></tr></thead>
                <tbody>
                    @forelse ($report['shifts'] as $shift)
                        <tr>
                            <td>{{ $shift->user->name }}</td>
                            <td>
                                @if ($shift->closed_at)
                                    Closed {{ $shift->closed_at->format('h:i A') }}
                                @else
                                    <span class="dr-open-badge"><span class="dr-open-dot"></span> OPEN</span>
                                @endif
                            </td>
                            <td>{{ $shift->expected_cash !== null ? '₦'.number_format((float) $shift->expected_cash) : '—' }}</td>
                            <td>{{ $shift->counted_cash !== null ? '₦'.number_format((float) $shift->counted_cash) : '—' }}</td>
                            <td>
                                @if ($shift->variance !== null)
                                    <span class="{{ (float) $shift->variance < 0 ? 'dr-bad' : ((float) $shift->variance > 0 ? 'dr-warn' : 'dr-good') }}">
                                        ₦{{ number_format((float) $shift->variance) }}
                                        {{ (float) $shift->variance < 0 ? 'SHORT (KUƊI SUN RAGU)' : ((float) $shift->variance > 0 ? 'OVER (ƘARI)' : 'DAIDAI' ) }}
                                        @if ((float) $shift->variance === 0.0)
                                            <x-filament::icon icon="heroicon-m-check-circle" class="dr-icon" />
                                        @endif
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="dr-empty">No shifts in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Sections --}}
        <div class="dr-card">
            <h3><x-filament::icon icon="heroicon-m-squares-2x2" class="dr-icon" /> By Section <span class="dr-ha">· Kowane Sashe</span></h3>
            <table>
                <thead><tr><th>Section</th><th>Sales</th></tr></thead>
                <tbody>
                    @forelse ($report['bySection'] as $section => $amount)
                        <tr><td>{{ $section }}</td><td class="dr-strong">₦{{ number_format($amount) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="dr-empty">No sales this day</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Top items --}}
        <div class="dr-card">
            <h3><x-filament::icon icon="heroicon-m-fire" class="dr-icon" /> Top Items <span class="dr-ha">· Abincin da Aka Fi Saya</span></h3>
            <table>
                <thead><tr><th>Item</th><th>Qty</th><th>Sales</th></tr></thead>
                <tbody>
                    @forelse ($report['topItems'] as $name => $row)
                        <tr><td>{{ $name }}</td><td>{{ $row['qty'] }}</td><td class="dr-strong">₦{{ number_format($row['total']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="dr-empty">No sales this day</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Voided receipts — full width, the owner's watch-list --}}
    <div class="dr-card {{ $report['voided']->isNotEmpty() ? 'dr-card-danger' : '' }}">
        <h3><x-filament::icon icon="heroicon-m-no-symbol" class="dr-icon" /> Voided Receipts <span class="dr-ha">· Rasidin da Aka Soke</span> ({{ $report['voided']->count() }})</h3>
        @if ($report['voided']->isEmpty())
            <p class="dr-empty" style="padding:.75rem 0;">None — clean day.</p>
        @else
            <table>
                <thead><tr><th>Receipt #</th><th>Cashier</th><th>Amount</th><th>Voided by</th><th>Reason</th><th>Time</th></tr></thead>
                <tbody>
                    @foreach ($report['voided'] as $sale)
                        <tr>
                            <td>{{ $sale->receipt_no }}</td>
                            <td>{{ $sale->user->name }}</td>
                            <td>₦{{ number_format((float) $sale->total) }}</td>
                            <td>{{ $sale->voidedBy?->name ?? '—' }}</td>
                            <td>{{ $sale->void_reason }}</td>
                            <td>{{ $sale->voided_at?->format('h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <style>
        .dr-icon { width: 1em; height: 1em; display: inline-block; vertical-align: -0.13em; }

        .dr-toolbar { display: flex; justify-content: space-between; align-items: end; gap: 1rem; flex-wrap: wrap; }
        .dr-range { display: flex; gap: .75rem; flex-wrap: wrap; align-items: end; }
        .dr-presets { display: flex; gap: .4rem; flex-wrap: wrap; }
        .dr-preset { padding: .5rem .9rem; border-radius: 999px; border: 1px solid rgba(120,120,120,.35); background: transparent; color: inherit; font-size: .82rem; font-weight: 600; cursor: pointer; transition: background .12s ease, border-color .12s ease, transform .08s ease; }
        .dr-preset:hover { border-color: #d97706; transform: translateY(-1px); }
        .dr-preset:active { transform: scale(.96); }
        .dr-preset.active { background: #d97706; border-color: #d97706; color: #fff; }
        .dr-datewrap label { display: block; font-size: .8rem; font-weight: 600; margin-bottom: .3rem; }
        .dr-datewrap input { padding: .55rem .8rem; border-radius: .6rem; border: 1px solid rgba(120,120,120,.35); background: transparent; color: inherit; }
        .dr-print { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.1rem; border-radius: .6rem; background: #d97706; color: #fff !important; font-weight: 700; font-size: .85rem; text-decoration: none; transition: background .12s ease; }
        .dr-print:hover { background: #b45309; }

        .dr-loading { display: flex; align-items: center; gap: .5rem; font-size: .85rem; font-weight: 600; opacity: .7; }
        .dr-spinner { width: 1em; height: 1em; border: 2px solid rgba(120,120,120,.35); border-top-color: #d97706; border-radius: 50%; display: inline-block; animation: dr-spin .7s linear infinite; }
        @keyframes dr-spin { to { transform: rotate(360deg); } }

        .dr-open-badge { display: inline-flex; align-items: center; gap: .35rem; color: #059669; font-weight: 700; }
        .dr-open-dot { width: .55rem; height: .55rem; border-radius: 50%; background: #10b981; animation: dr-pulse 1.4s ease infinite; }
        @keyframes dr-pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .45; transform: scale(.8); } }

        .dr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .8rem; }
        .dr-stat { background: var(--dr-card, #fff); border: 1px solid rgba(120,120,120,.22); border-radius: .9rem; padding: 1rem 1.1rem; }
        .dark .dr-stat { background: #18181b; }
        .dr-stat span { font-size: .78rem; font-weight: 700; opacity: .65; text-transform: uppercase; letter-spacing: .04em; }
        .dr-stat strong { display: block; font-size: 1.45rem; font-weight: 800; margin: .2rem 0; }
        .dr-stat small { opacity: .55; font-size: .75rem; }
        .dr-stat-main { border-top: 4px solid #d97706; }
        .dr-stat-main strong { color: #d97706; }
        .dark .dr-stat-main strong { color: #fbbf24; }

        .dr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: .8rem; }
        .dr-card { background: var(--dr-card, #fff); border: 1px solid rgba(120,120,120,.22); border-radius: .9rem; padding: 1.1rem 1.2rem; }
        .dark .dr-card { background: #18181b; }
        .dr-card h3 { font-size: .95rem; font-weight: 800; margin-bottom: .7rem; }
        .dr-card table { width: 100%; border-collapse: collapse; font-size: .87rem; }
        .dr-card th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; opacity: .55; padding: .35rem 0; border-bottom: 1px solid rgba(120,120,120,.25); }
        .dr-card td { padding: .45rem 0; border-bottom: 1px solid rgba(120,120,120,.1); }
        .dr-strong { font-weight: 700; }
        .dr-empty { opacity: .5; text-align: center; }
        .dr-good { color: #059669; font-weight: 700; }
        .dr-warn { color: #d97706; font-weight: 700; }
        .dr-bad { color: #dc2626; font-weight: 800; }
        .dr-card-danger { border-color: rgba(220,38,38,.4); }
        .dr-ha { opacity: .55; font-weight: 500; font-size: .85em; font-style: italic; }
    </style>
</x-filament-panels::page>
