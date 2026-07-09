<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report — {{ $report['day']->format('d M Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; max-width: 190mm; margin: 0 auto; padding: 10mm 5mm; }
        h1 { font-size: 18px; }
        h2 { font-size: 13px; margin: 14px 0 6px; border-bottom: 2px solid #2563eb; padding-bottom: 3px; }
        .sub { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #555; border-bottom: 1px solid #999; padding: 3px 4px; }
        td { padding: 4px; border-bottom: 1px solid #ddd; }
        .num { text-align: right; }
        .strong { font-weight: bold; }
        .bad { color: #b91c1c; font-weight: bold; }
        .summary { display: flex; gap: 10px; margin: 10px 0; }
        .box { flex: 1; border: 1px solid #bbb; border-radius: 6px; padding: 8px 10px; }
        .box span { font-size: 10px; text-transform: uppercase; color: #555; display: block; }
        .box strong { font-size: 16px; }
        .sign { margin-top: 30px; display: flex; justify-content: space-between; }
        .sign div { width: 45%; border-top: 1px solid #333; text-align: center; padding-top: 4px; font-size: 11px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ config('app.name') }} — Daily Sales Report</h1>
    <div class="sub">{{ $report['day']->format('l, d F Y') }} · Printed {{ now()->format('d/m/Y h:i A') }} by {{ auth()->user()->name }}</div>

    <div class="summary">
        <div class="box"><span>Total Sales</span><strong>₦{{ number_format($report['total']) }}</strong></div>
        <div class="box"><span>Cash</span><strong>₦{{ number_format($report['byMethod']['cash']) }}</strong></div>
        <div class="box"><span>Transfer</span><strong>₦{{ number_format($report['byMethod']['transfer']) }}</strong></div>
        <div class="box"><span>POS Card</span><strong>₦{{ number_format($report['byMethod']['pos']) }}</strong></div>
        <div class="box"><span>Receipts</span><strong>{{ $report['receipts'] }}</strong></div>
    </div>

    <h2>By Cashier</h2>
    <table>
        <thead><tr><th>Cashier</th><th class="num">Receipts</th><th class="num">Cash</th><th class="num">Total</th></tr></thead>
        <tbody>
            @foreach ($report['byCashier'] as $row)
                <tr><td>{{ $row['name'] }}</td><td class="num">{{ $row['receipts'] }}</td><td class="num">₦{{ number_format($row['cash']) }}</td><td class="num strong">₦{{ number_format($row['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Shifts &amp; Drawer Variance</h2>
    <table>
        <thead><tr><th>Cashier</th><th>Opened</th><th>Closed</th><th class="num">Expected</th><th class="num">Counted</th><th class="num">Variance</th></tr></thead>
        <tbody>
            @foreach ($report['shifts'] as $shift)
                <tr>
                    <td>{{ $shift->user->name }}</td>
                    <td>{{ $shift->opened_at->format('h:i A') }}</td>
                    <td>{{ $shift->closed_at?->format('h:i A') ?? 'OPEN' }}</td>
                    <td class="num">{{ $shift->expected_cash !== null ? '₦'.number_format((float) $shift->expected_cash) : '—' }}</td>
                    <td class="num">{{ $shift->counted_cash !== null ? '₦'.number_format((float) $shift->counted_cash) : '—' }}</td>
                    <td class="num {{ (float) ($shift->variance ?? 0) < 0 ? 'bad' : 'strong' }}">
                        {{ $shift->variance !== null ? '₦'.number_format((float) $shift->variance) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>By Section</h2>
    <table>
        <thead><tr><th>Section</th><th class="num">Sales</th></tr></thead>
        <tbody>
            @foreach ($report['bySection'] as $section => $amount)
                <tr><td>{{ $section }}</td><td class="num strong">₦{{ number_format($amount) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Voided Receipts ({{ $report['voided']->count() }})</h2>
    @if ($report['voided']->isEmpty())
        <p>None.</p>
    @else
        <table>
            <thead><tr><th>Receipt #</th><th>Cashier</th><th class="num">Amount</th><th>Voided by</th><th>Reason</th></tr></thead>
            <tbody>
                @foreach ($report['voided'] as $sale)
                    <tr>
                        <td>{{ $sale->receipt_no }}</td>
                        <td>{{ $sale->user->name }}</td>
                        <td class="num">₦{{ number_format((float) $sale->total) }}</td>
                        <td>{{ $sale->voidedBy?->name ?? '—' }}</td>
                        <td>{{ $sale->void_reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="sign">
        <div>Accountant</div>
        <div>Owner</div>
    </div>

    <div class="no-print" style="margin-top:16px; text-align:center;">
        <button onclick="window.print()" style="padding:8px 16px;">Print again</button>
    </div>
</body>
</html>
