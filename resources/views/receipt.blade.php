<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->receipt_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; width: 76mm; margin: 0 auto; padding: 4mm 2mm; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .rule { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; }
        .items td { padding: 1px 0; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        .opt { padding-left: 10px; font-size: 11px; }
        .total { font-size: 16px; font-weight: bold; }
        .copy-label { text-align: center; font-weight: bold; border: 1px solid #000; padding: 2px; margin-bottom: 6px; }
        .kitchen-header { text-align: center; font-weight: bold; font-size: 15px; border: 2px solid #000; padding: 4px; margin-bottom: 6px; }
        .kitchen-item { font-size: 14px; font-weight: bold; padding: 2px 0; }
        .cut { text-align: center; margin: 10px 0; font-size: 10px; }
        .voided { border: 3px solid #000; text-align: center; font-size: 18px; font-weight: bold; padding: 4px; margin: 6px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    {{-- ============ CUSTOMER COPY ============ --}}
    <div class="copy-label">CUSTOMER COPY</div>
    <div class="center bold" style="font-size:15px;">{{ config('app.name') }}</div>
    <div class="center">Kano, Nigeria</div>
    <div class="rule"></div>
    <div class="row"><span>Receipt:</span><span class="bold">{{ $sale->receipt_no }}</span></div>
    <div class="row"><span>Date:</span><span>{{ $sale->created_at->format('d/m/Y h:i A') }}</span></div>
    <div class="row"><span>Cashier:</span><span>{{ $sale->user->name }}</span></div>

    @if ($sale->status === 'voided')
        <div class="voided">*** VOIDED ***</div>
    @endif

    <div class="rule"></div>
    <table class="items">
        @foreach ($sale->items as $item)
            <tr>
                <td>
                    {{ $item->quantity }}x {{ $item->product_name }}
                    @foreach ($item->options ?? [] as $opt)
                        <div class="opt">+ {{ $opt['name'] }} (₦{{ number_format((float) $opt['price']) }})</div>
                    @endforeach
                </td>
                <td style="text-align:right;">₦{{ number_format((float) $item->line_total) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="rule"></div>
    @if (! empty($sale->charges))
        <div class="row"><span>Subtotal:</span><span>₦{{ number_format((float) $sale->subtotal) }}</span></div>
        @foreach ($sale->charges as $charge)
            <div class="row"><span>{{ $charge['name'] }}:</span><span>+₦{{ number_format((float) $charge['amount']) }}</span></div>
        @endforeach
        <div class="rule"></div>
    @endif
    <div class="row total"><span>TOTAL</span><span>₦{{ number_format((float) $sale->total) }}</span></div>
    <div class="row"><span>Paid via:</span><span class="bold">{{ strtoupper($sale->payment_method) }}</span></div>
    @if ($sale->payment_method === 'cash' && $sale->amount_paid !== null)
        <div class="row"><span>Received:</span><span>₦{{ number_format((float) $sale->amount_paid) }}</span></div>
        <div class="row"><span>Change:</span><span>₦{{ number_format((float) $sale->change_due) }}</span></div>
    @endif
    @if ($sale->payment_reference)
        <div class="row"><span>Ref:</span><span>{{ $sale->payment_reference }}</span></div>
    @endif
    <div class="rule"></div>
    <div class="center">Nagode! Thank you, come again!</div>

    {{-- ============ ONE KITCHEN TICKET PER SECTION ============ --}}
    @foreach ($sale->items->groupBy('section') as $section => $items)
        <div class="cut">✂ ------------------------------- ✂</div>
        <div class="kitchen-header">{{ strtoupper($section) }} SECTION</div>
        <div class="row"><span>Receipt:</span><span class="bold">{{ $sale->receipt_no }}</span></div>
        <div class="row"><span>Time:</span><span>{{ $sale->created_at->format('h:i A') }}</span></div>
        <div class="rule"></div>
        @foreach ($items as $item)
            <div class="kitchen-item">{{ $item->quantity }}x {{ $item->product_name }}</div>
            @foreach ($item->options ?? [] as $opt)
                <div class="opt">+ {{ $opt['name'] }}</div>
            @endforeach
        @endforeach
        <div class="rule"></div>
        <div class="center bold">Serve ONLY against this ticket</div>
        <div class="center">Kada a bayar da abinci sai da wannan takarda</div>
    @endforeach

    <div class="no-print center" style="margin-top:12px;">
        <button onclick="window.print()" style="padding:8px 16px;">Print again</button>
    </div>
</body>
</html>
