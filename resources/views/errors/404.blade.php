<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Página não encontrada · Alfarei CNC</title>
    <link rel="stylesheet" href="{{ asset('css/error-404.css') }}?v={{ filemtime(public_path('css/error-404.css')) }}">
</head>
<body>
    <main class="not-found">
        <div class="not-found__header">
            <a class="not-found__brand" href="{{ route('loja.index') }}" aria-label="Alfarei CNC — página inicial">
                <img src="{{ asset('images/alfarei-logo.png') }}" alt="Alfarei">
            </a>
            <span class="not-found__eyebrow">CORTE & CRIAÇÃO SOB MEDIDA</span>
        </div>

        <section class="not-found__content" aria-labelledby="not-found-title">
            <div class="not-found__copy">
                <span class="not-found__tag"><span aria-hidden="true"></span> ERRO 404 · PÁGINA NÃO ENCONTRADA</span>
                <h1 id="not-found-title">Este caminho saiu <em>do contorno.</em></h1>
                <p>A página que você procura não está mais aqui ou o endereço foi digitado incorretamente. Mas sua próxima ideia ainda tem lugar na Alfarei.</p>
                <div class="not-found__actions">
                    @if(auth()->check())
                        <a class="not-found__button not-found__button--primary" href="{{ route('dashboard') }}">Voltar ao sistema <span aria-hidden="true">→</span></a>
                        <a class="not-found__button not-found__button--secondary" href="{{ route('loja.index') }}">Explorar a loja <span aria-hidden="true">↗</span></a>
                    @else
                        <a class="not-found__button not-found__button--primary" href="{{ route('loja.index') }}">Explorar a loja <span aria-hidden="true">↗</span></a>
                        <a class="not-found__button not-found__button--secondary" href="{{ route('loja.tracking') }}">Rastrear pedido <span aria-hidden="true">→</span></a>
                    @endif
                </div>
            </div>

            <div class="not-found__art" aria-hidden="true">
                <div class="not-found__art-label"><span>ALFAREI / LASER</span><span>ARQUIVO NÃO LOCALIZADO</span></div>
                <svg class="not-found__drawing" viewBox="0 0 560 470" fill="none" xmlns="http://www.w3.org/2000/svg" role="presentation">
                    <defs>
                        <pattern id="grid" width="28" height="28" patternUnits="userSpaceOnUse"><path d="M 28 0 L 0 0 0 28" stroke="#d7ded2" stroke-width="1"/></pattern>
                        <filter id="shadow" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="20" stdDeviation="17" flood-color="#172c22" flood-opacity=".16"/></filter>
                    </defs>
                    <rect x="0" y="0" width="560" height="470" fill="url(#grid)" opacity=".48"/>
                    <path d="M77 68h406M77 402h406" stroke="#c4cfc2" stroke-width="1" stroke-dasharray="4 8"/>
                    <path d="M67 94h426v285H67z" stroke="#91a998" stroke-width="1.5" stroke-dasharray="8 9"/>
                    <path d="M77 105h406v264H77z" fill="#d3b88a" filter="url(#shadow)"/>
                    <path d="M88 116h384v242H88z" fill="#e5cea5"/>
                    <text x="280" y="305" text-anchor="middle" fill="#f8f6ec" stroke="#385b47" stroke-width="2.5" paint-order="stroke" font-family="Arial,sans-serif" font-size="168" font-weight="900" letter-spacing="-16">404</text>
                    <path d="M112 328h352" stroke="#c69b51" stroke-width="3" stroke-dasharray="5 6"/>
                    <circle cx="96" cy="103" r="5" fill="#d6a646"/><circle cx="484" cy="370" r="5" fill="#d6a646"/>
                    <path d="M476 99l-24 25m12-32-1 17m18-4-18 1" stroke="#b57434" stroke-width="2" stroke-linecap="round"/>
                    <path d="M467 107l-14 14" stroke="#f6d981" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="70" cy="92" r="2" fill="#fff5c0"/><circle cx="59" cy="81" r="2" fill="#fff5c0"/>
                    <path d="M74 395h36m344 0h34" stroke="#77907c" stroke-width="2"/>
                </svg>
                <div class="not-found__art-footer"><span>PEÇA Nº 404</span><span>REFAZER TRAJETO ↗</span></div>
            </div>
        </section>

        <footer class="not-found__footer"><span>ALFAREI CNC · IDEIAS QUE GANHAM FORMA</span><span>Volte ao início para continuar.</span></footer>
    </main>
</body>
</html>
