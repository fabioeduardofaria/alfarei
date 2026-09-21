<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Alfarei CNC' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/quotes.css') }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">A</span><span><b>ALFAREI</b><small>CNC · OPERAÇÃO</small></span></a>
        <nav>
            <p>VISÃO</p>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ <span>Painel</span></a>
            <p>CADASTROS</p>
            <a class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">◉ <span>Clientes</span></a>
            <a class="{{ request()->routeIs('materiais.*') ? 'active' : '' }}" href="{{ route('materiais.index') }}">◇ <span>Materiais</span></a>
            <a class="{{ request()->routeIs('fornecedores.*') ? 'active' : '' }}" href="{{ route('fornecedores.index') }}">◌ <span>Fornecedores</span></a>
            <a class="{{ request()->routeIs('produtos.*') ? 'active' : '' }}" href="{{ route('produtos.index') }}">▧ <span>Produtos e serviços</span></a>
            <p>PRÓXIMAS ETAPAS</p>
            <a class="{{ request()->routeIs('orcamentos.*') ? 'active' : '' }}" href="{{ route('orcamentos.index') }}">◫ <span>Orçamentos</span></a>
            <a class="{{ request()->routeIs('pedidos.*') ? 'active' : '' }}" href="{{ route('pedidos.index') }}">▣ <span>Pedidos</span></a>
            <a class="{{ request()->routeIs('producao.*') ? 'active' : '' }}" href="{{ route('producao.index') }}">◒ <span>Produção</span></a>
            <a class="{{ request()->routeIs('entregas.*') ? 'active' : '' }}" href="{{ route('entregas.index') }}">▹ <span>Entregas</span></a>
            <a class="{{ request()->routeIs('compras.*') ? 'active' : '' }}" href="{{ route('compras.index') }}">▤ <span>Compras</span></a>
            <a class="{{ request()->routeIs('financeiro.*') ? 'active' : '' }}" href="{{ route('financeiro.index') }}">▤ <span>Financeiro</span></a>
            <a class="{{ request()->routeIs('loja.settings.*') ? 'active' : '' }}" href="{{ route('loja.settings.edit') }}">◉ <span>Configurar loja</span></a>
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="user-panel">@csrf<div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><div><b>{{ auth()->user()->name }}</b><small>{{ ucfirst(auth()->user()->role) }}</small></div><button title="Sair">↪</button></form>
    </aside>
    <main>
        <header class="topbar"><div class="mobile-brand">ALFAREI CNC</div><div class="topbar-search">⌕ <input placeholder="Buscar em breve..." disabled></div><span class="date">{{ now()->translatedFormat('l, d \d\e F') }}</span></header>
        <div class="content">@include('partials.flash') @yield('content')</div>
    </main>
</div>
</body>
</html>
