<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Ahlan wa Sahlan') }} — Menu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #faf6ee; color: #1c1917; min-height: 100vh; }
        .page { max-width: 560px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }

        .hero { text-align: center; padding: 2rem 0 1.5rem; }
        .hero-mark { width: 4rem; height: 4rem; border-radius: 1.1rem; background: linear-gradient(135deg,#f59e0b,#b45309); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto .8rem; box-shadow: 0 8px 24px rgba(217,119,6,.35); }
        .hero h1 { font-size: 1.5rem; font-weight: 800; }
        .hero p { opacity: .65; font-size: .9rem; margin-top: .3rem; }
        .hero .ha { font-style: italic; font-size: .82rem; opacity: .55; display: block; margin-top: .15rem; }

        .card { background: #fff; border: 1px solid rgba(120,120,120,.18); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem; }
        .card h2 { font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; opacity: .55; margin-bottom: .8rem; }

        .table-btn { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 1rem 1.1rem; border-radius: .8rem; background: #d97706; color: #fff; font-weight: 800; font-size: 1rem; text-decoration: none; margin-bottom: .6rem; box-shadow: 0 4px 14px rgba(217,119,6,.3); }
        .table-btn:active { transform: scale(.98); }
        .table-btn small { font-weight: 600; opacity: .85; font-size: .78rem; }
        .table-btn:last-child { margin-bottom: 0; }

        .preview { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
        .dish { border-radius: .7rem; overflow: hidden; border: 1px solid rgba(120,120,120,.15); background: #fff; }
        .dish-img { aspect-ratio: 1; background: rgba(217,119,6,.12); position: relative; }
        .dish-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .dish-img span { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 800; color: #d97706; opacity: .7; }
        .dish-name { font-size: .68rem; font-weight: 700; padding: .35rem .45rem .1rem; line-height: 1.2; }
        .dish-price { font-size: .7rem; font-weight: 800; color: #d97706; padding: 0 .45rem .4rem; }

        .note { text-align: center; font-size: .8rem; opacity: .6; margin: 1rem 0; }
        .staff { display: block; text-align: center; font-size: .78rem; color: #b45309; font-weight: 600; text-decoration: none; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div class="hero-mark">🍛</div>
            <h1>{{ config('app.name', 'Ahlan wa Sahlan') }}</h1>
            <p>Fresh Northern Nigerian cooking, made to order.</p>
            <span class="ha">Abinci mai daɗi, ana yin sa nan take.</span>
        </div>

        @if ($tables->isNotEmpty())
            <div class="card">
                <h2>Order from your table · Yi oda daga teburinka</h2>
                @foreach ($tables as $table)
                    <a href="{{ $table->orderUrl() }}" class="table-btn">
                        {{ $table->name }}
                        <small>View menu & order →</small>
                    </a>
                @endforeach
            </div>
            <p class="note">Sitting at one of our tables? Tap it above — or scan the QR code on the table — to browse the menu and send your order straight to the kitchen. Pay at the counter when you're done.</p>
        @endif

        @if ($dishes->isNotEmpty())
            <div class="card">
                <h2>From the menu · Daga cikin menu</h2>
                <div class="preview">
                    @foreach ($dishes as $dish)
                        <div class="dish">
                            <div class="dish-img">
                                @if ($dish->imageUrl())
                                    <img src="{{ $dish->imageUrl() }}" alt="{{ $dish->name }}" loading="lazy" />
                                @else
                                    <span>{{ mb_strtoupper(mb_substr($dish->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="dish-name">{{ $dish->name }}</div>
                            <div class="dish-price">₦{{ number_format((float) $dish->price) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <a href="/admin" class="staff">Staff login · Shiga ma'aikata</a>
    </div>
</body>
</html>
