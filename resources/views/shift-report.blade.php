<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Z-Report — Shift #{{ $shift->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; width: 76mm; margin: 0 auto; padding: 4mm 2mm; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .rule { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; padding: 1px 0; }
        .big { font-size: 15px; font-weight: bold; }
        .variance { border: 2px solid #000; padding: 4px; text-align: center; font-size: 14px; font-weight: bold; margin: 6px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    @php
        $completed = $shift->sales->where('status', 'completed');
        $voided = $shift->sales->where('status', 'voided');
        $cash = $completed->where('payment_method', 'cash')->sum('total');
        $transfer = $completed->where('payment_method', 'transfer')->sum('total');
        $pos = $completed->where('payment_method', 'pos')->sum('total');
        $sectionTotals = $completed->flatMap->items->groupBy('section')->map->sum('line_total');
    @endphp

    <div class="center bold" style="font-size:15px;">{{ config('app.name') }}</div>
    <div class="center big">Z-REPORT (END OF SHIFT)</div>
    <div class="rule"></div>
    <div class="row"><span>Shift #:</span><span>{{ $shift->id }}</span></div>
    <div class="row"><span>Cashier:</span><span class="bold">{{ $shift->user->name }}</span></div>
    <div class="row"><span>Opened:</span><span>{{ $shift->opened_at->format('d/m/Y h:i A') }}</span></div>
    <div class="row"><span>Closed:</span><span>{{ $shift->closed_at?->format('d/m/Y h:i A') ?? 'STILL OPEN' }}</span></div>

    <div class="rule"></div>
    <div class="bold">SALES SUMMARY</div>
    <div class="row"><span>Receipts issued:</span><span>{{ $completed->count() }}</span></div>
    <div class="row"><span>Voided receipts:</span><span>{{ $voided->count() }}</span></div>
    <div class="row"><span>Cash sales:</span><span>₦{{ number_format($cash) }}</span></div>
    <div class="row"><span>Transfer sales:</span><span>₦{{ number_format($transfer) }}</span></div>
    <div class="row"><span>POS card sales:</span><span>₦{{ number_format($pos) }}</span></div>
    <div class="row big"><span>TOTAL SALES:</span><span>₦{{ number_format($cash + $transfer + $pos) }}</span></div>

    <div class="rule"></div>
    <div class="bold">BY SECTION</div>
    @foreach ($sectionTotals as $section => $amount)
        <div class="row"><span>{{ $section }}:</span><span>₦{{ number_format((float) $amount) }}</span></div>
    @endforeach

    <div class="rule"></div>
    <div class="bold">CASH DRAWER</div>
    <div class="row"><span>Opening float:</span><span>₦{{ number_format((float) $shift->opening_float) }}</span></div>
    <div class="row"><span>+ Cash sales:</span><span>₦{{ number_format($cash) }}</span></div>
    <div class="row big"><span>EXPECTED:</span><span>₦{{ number_format((float) ($shift->expected_cash ?? ((float) $shift->opening_float + $cash))) }}</span></div>
    @if ($shift->counted_cash !== null)
        <div class="row big"><span>COUNTED:</span><span>₦{{ number_format((float) $shift->counted_cash) }}</span></div>
        <div class="variance">
            VARIANCE: ₦{{ number_format((float) $shift->variance) }}
            {{ (float) $shift->variance === 0.0 ? '— BALANCED ✓' : ((float) $shift->variance < 0 ? '— SHORT!' : '— OVER') }}
        </div>
    @endif

    @if ($shift->notes)
        <div class="rule"></div>
        <div>Notes: {{ $shift->notes }}</div>
    @endif

    <div class="rule"></div>
    <div class="center">Signature: ______________________</div>
    <div class="center" style="margin-top:4px;">(Cashier)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Signature: ______________________ (Owner)</div>

    <div class="no-print center" style="margin-top:12px;">
        <button onclick="window.print()" style="padding:8px 16px;">Print again</button>
    </div>
</body>
</html>
