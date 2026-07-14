<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR — {{ $table->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #faf6ee; }
        .card { width: 320px; background: #fff; border: 2px solid #1c1917; border-radius: 1rem; padding: 2rem 1.5rem; text-align: center; }
        .mark { font-size: 2rem; margin-bottom: .3rem; }
        .name { font-weight: 800; font-size: 1.1rem; margin-bottom: .1rem; }
        .table-name { font-weight: 800; font-size: 1.6rem; color: #d97706; margin: .6rem 0 1rem; }
        .qr { width: 220px; height: 220px; margin: 0 auto 1rem; }
        .qr svg { width: 100%; height: 100%; }
        .instructions { font-size: .85rem; font-weight: 600; margin-bottom: .2rem; }
        .instructions-ha { font-size: .78rem; opacity: .6; font-style: italic; }
        .no-print { margin-top: 1.5rem; }
        @media print { .no-print { display: none; } body { background: #fff; } }
    </style>
</head>
<body onload="window.print()">
    <div class="card">
        <div class="mark">🍛</div>
        <div class="name">{{ config('app.name', 'Ahlan wa Sahlan') }}</div>
        <div class="table-name">{{ $table->name }}</div>
        <div class="qr">{!! $table->qrSvg() !!}</div>
        <div class="instructions">Scan to view the menu & order · Ka duba menu ka yi oda</div>
        <div class="instructions-ha">Pay at the counter when you're done · Ka biya a kanti bayan ka gama</div>
    </div>
    <div class="no-print" style="text-align:center;">
        <button onclick="window.print()" style="padding:8px 16px;">Print again</button>
    </div>
</body>
</html>
