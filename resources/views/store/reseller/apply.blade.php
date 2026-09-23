@extends('store.layout')

@section('content')
<section class="reseller-store-page">
    <div class="reseller-store-intro"><p class="store-eyebrow">PARCEIROS ALFAREI</p><h1>Revenda no seu ritmo.</h1><p>Você não perde o perfil por ficar um tempo sem comprar. O preço de revendedor depende da quantidade mínima de cada produto em cada pedido.</p><a href="{{ route('loja.account.profile') }}">← Minha conta</a></div>
    <div class="reseller-store-panel">
        @if($customer->hasResellerPricing())
            <span class="reseller-badge approved">Perfil aprovado</span><h2>Seu acesso de revendedor está ativo.</h2><p>Mesmo que compre ocasionalmente, seu perfil permanece. Pedidos abaixo do mínimo do produto seguem o preço normal.</p><a class="store-primary" href="{{ route('loja.index') }}#catalogo">Explorar produtos →</a>
        @elseif($customer->customer_group === 'wholesale')
            <span class="reseller-badge approved">Atacado</span><h2>Sua conta já tem condições de atacado.</h2><p>Para alterar o perfil comercial, fale com a equipe Alfarei.</p>
        @elseif($application?->status === 'pending')
            <span class="reseller-badge pending">Em análise</span><h2>Recebemos sua solicitação.</h2><p>Enviada em {{ $application->updated_at->format('d/m/Y') }}. Você continua vendo os preços de cliente final até a aprovação.</p>
        @else
            @if($application?->status === 'needs_info')<span class="reseller-badge pending">Informações necessárias</span><p>{{ $application->decision_note }}</p>@elseif($application?->status === 'rejected')<span class="reseller-badge">Solicitação anterior não aprovada</span><p>{{ $application->decision_note }}</p><p>Você pode enviar uma nova solicitação com informações atualizadas.</p>@elseif($customer->reseller_status === 'suspended')<span class="reseller-badge">Perfil em revisão</span><p>Seu acesso especial está suspenso por decisão da equipe. Você pode solicitar nova análise.</p>@endif
            <h2>{{ $application?->status === 'needs_info' ? 'Complete sua solicitação' : 'Solicitar perfil de revendedor' }}</h2>
            <p>Conte onde e como você comercializa produtos. A equipe analisará seu pedido; não há exigência de compras frequentes para manter o perfil.</p>
            <form method="POST" action="{{ route('loja.reseller.store') }}">@csrf
                <label>Nome do negócio <small>(opcional)</small><input name="business_name" value="{{ old('business_name', $application?->business_name) }}" maxlength="150" placeholder="Ex.: Studio Casa Presentes"></label>
                <label>Onde você vende?<select name="sales_channel" required><option value="">Selecione</option>@foreach(['physical' => 'Loja física', 'online' => 'Loja virtual', 'social' => 'Redes sociais', 'marketplace' => 'Marketplace', 'other' => 'Outro canal'] as $value => $label)<option value="{{ $value }}" @selected(old('sales_channel', $application?->sales_channel) === $value)>{{ $label }}</option>@endforeach</select>@error('sales_channel')<small class="account-error">{{ $message }}</small>@enderror</label>
                <label>Link da loja, rede social ou catálogo <small>(opcional)</small><input type="url" name="sales_url" value="{{ old('sales_url', $application?->sales_url) }}" maxlength="500" placeholder="https://...">@error('sales_url')<small class="account-error">{{ $message }}</small>@enderror</label>
                <label>Conte sobre sua atividade<textarea name="description" rows="5" minlength="20" maxlength="2000" required placeholder="O que você vende, para quem vende e como pretende trabalhar com os produtos Alfarei?">{{ old('description', $application?->description) }}</textarea>@error('description')<small class="account-error">{{ $message }}</small>@enderror</label>
                <button class="store-primary" type="submit">Enviar para análise →</button>
            </form>
        @endif
        @error('reseller')<small class="account-error">{{ $message }}</small>@enderror
    </div>
</section>
@endsection
