<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Alfarei CNC' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/quotes.css') }}?v={{ filemtime(public_path('css/quotes.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/quote-display.css') }}?v={{ filemtime(public_path('css/quote-display.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
    <link rel="stylesheet" href="{{ asset('css/system-polish.css') }}?v={{ filemtime(public_path('css/system-polish.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/crm.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}?v={{ filemtime(public_path('css/brand.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/finance.css') }}?v={{ filemtime(public_path('css/finance.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/resellers.css') }}?v={{ filemtime(public_path('css/resellers.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/display-configurator.css') }}?v={{ filemtime(public_path('css/display-configurator.css')) }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="Alfarei CNC — painel"><img class="brand-logo" src="{{ asset('images/alfarei-logo.png') }}" alt="Alfarei"></a>
        <nav>
            <p>VISÃO</p>
            @if(auth()->user()->canAccess('dashboard'))<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ <span>Painel</span></a>@endif
            <p>CADASTROS</p>
            @if(auth()->user()->canAccess('customers'))<a class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">◉ <span>Clientes</span></a>@endif
            @if(auth()->user()->canAccess('customers'))<a class="{{ request()->routeIs('revendedores.*') ? 'active' : '' }}" href="{{ route('revendedores.index') }}">♧ <span>Programa de revenda</span></a>@endif
            @if(auth()->user()->canAccess('crm'))<a class="{{ request()->routeIs('crm.*') ? 'active' : '' }}" href="{{ route('crm.index') }}">◌ <span>CRM e oportunidades</span></a>@endif
            @if(auth()->user()->canAccess('materials'))<a class="{{ request()->routeIs('materiais.*') ? 'active' : '' }}" href="{{ route('materiais.index') }}">◇ <span>Materiais</span></a>@endif
            @if(auth()->user()->canAccess('suppliers'))<a class="{{ request()->routeIs('fornecedores.*') ? 'active' : '' }}" href="{{ route('fornecedores.index') }}">◌ <span>Fornecedores</span></a>@endif
            @if(auth()->user()->canAccess('products'))<a class="{{ request()->routeIs('produtos.*') ? 'active' : '' }}" href="{{ route('produtos.index') }}">▧ <span>Produtos e serviços</span></a>@endif
            <p>PRÓXIMAS ETAPAS</p>
            @if(auth()->user()->canAccess('quotes'))<a class="{{ request()->routeIs('orcamentos.*') ? 'active' : '' }}" href="{{ route('orcamentos.index') }}">◫ <span>Orçamentos</span></a>@endif
            @if(auth()->user()->canAccess('orders'))<a class="{{ request()->routeIs('pedidos.*') ? 'active' : '' }}" href="{{ route('pedidos.index') }}">▣ <span>Pedidos</span></a>@endif
            @if(auth()->user()->canAccess('production'))<a class="{{ request()->routeIs('producao.*') ? 'active' : '' }}" href="{{ route('producao.index') }}">◒ <span>Produção</span></a>@endif
            @if(auth()->user()->canAccess('production'))<a class="{{ request()->routeIs('maquinas.*') ? 'active' : '' }}" href="{{ route('maquinas.index') }}">⚙ <span>Máquinas e custos</span></a>@endif
            @if(auth()->user()->canAccess('deliveries'))<a class="{{ request()->routeIs('entregas.*') ? 'active' : '' }}" href="{{ route('entregas.index') }}">▹ <span>Entregas</span></a>@endif
            @if(auth()->user()->canAccess('notifications'))<a class="{{ request()->routeIs('notificacoes.*') ? 'active' : '' }}" href="{{ route('notificacoes.index') }}">✉ <span>Notificações</span></a>@endif
            @if(auth()->user()->canAccess('purchases'))<a class="{{ request()->routeIs('compras.*') ? 'active' : '' }}" href="{{ route('compras.index') }}">▤ <span>Compras</span></a>@endif
            @if(auth()->user()->canAccess('finance'))<a class="{{ request()->routeIs('financeiro.*') ? 'active' : '' }}" href="{{ route('financeiro.index') }}">▤ <span>Financeiro</span></a>@endif
            @if(auth()->user()->canAccess('store_settings'))<a class="{{ request()->routeIs('loja.settings.*') ? 'active' : '' }}" href="{{ route('loja.settings.edit') }}">◉ <span>Configurar loja</span></a>@endif
            @if(auth()->user()->canAccess('store_settings'))<a class="{{ request()->routeIs('displays.config.*') ? 'active' : '' }}" href="{{ route('displays.config.edit') }}">▤ <span>Configurador de displays</span></a>@endif
            @if(auth()->user()->canAccess('store_settings'))<a class="{{ request()->routeIs('texts.config.*') ? 'active' : '' }}" href="{{ route('texts.config.edit') }}">✎ <span>Nomes e textos</span></a>@endif
            @if(auth()->user()->canAccess('users'))<a class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">♙ <span>Usuários e acessos</span></a>@endif
        </nav>
        <div class="user-panel"><div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><div><a href="{{ route('perfil.edit') }}"><b>{{ auth()->user()->name }}</b><small>Minha conta</small></a></div><form method="POST" action="{{ route('logout') }}">@csrf<button title="Sair">↪</button></form></div>
    </aside>
    <main>
        <header class="topbar"><div class="mobile-brand">ALFAREI CNC</div><div class="topbar-search">⌕ <input placeholder="Buscar em breve..." disabled></div><span class="date">{{ now()->translatedFormat('l, d \d\e F') }}</span></header>
        <div class="content">@include('partials.flash') @yield('content')</div>
    </main>
</div>
</body>
</html>
