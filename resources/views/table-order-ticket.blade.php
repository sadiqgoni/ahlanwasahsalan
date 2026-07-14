<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Table Order — {{ $order->diningTable->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; width: 76mm; margin: 0 auto; padding: 4mm 2mm; }
        .center { text-align: center; }
        .rule { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; }
        .opt { padding-left: 10px; font-size: 11px; }
        .kitchen-header { text-align: center; font-weight: bold; font-size: 15px; border: 2px solid #000; padding: 4px; margin-bottom: 6px; }
        .kitchen-item { font-size: 14px; font-weight: bold; padding: 2px 0; }
        .cut { text-align: center; margin: 10px 0; font-size: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    @foreach ($order->items->groupBy('section') as $section => $items)
        @if (! $loop->first)
            <div class="cut">✂ ------------------------------- ✂</div>
        @endif
        <div class="kitchen-header">{{ strtoupper($section) }} SECTION</div>
        <div class="row"><span>{{ $order->diningTable->name }}</span><span class="bold">Order #{{ $order->id }}</span></div>
        <div class="row"><span>Time:</span><span>{{ $order->reviewed_at?->format('h:i A') ?? $order->created_at->format('h:i A') }}</span></div>
        @if ($order->customer_name)
            <div class="row"><span>Name:</span><span>{{ $order->customer_name }}</span></div>
        @endif
        <div class="rule"></div>
        @foreach ($items as $item)
            <div class="kitchen-item">{{ $item->quantity }}x {{ $item->product_name }}</div>
            @foreach ($item->options ?? [] as $opt)
                <div class="opt">+ {{ $opt['name'] }}</div>
            @endforeach
        @endforeach
        @if ($order->note)
            <div class="rule"></div>
            <div class="opt" style="font-weight:bold;">Note: {{ $order->note }}</div>
        @endif
        <div class="rule"></div>
        <div class="center bold">Ordered via table QR code</div>
        <div class="center">An yi oda ta QR code na tebur</div>
    @endforeach

    <div class="no-print center" style="margin-top:12px;">
        <button onclick="window.print()" style="padding:8px 16px;">Print again</button>
    </div>
</body>
</html>
