@extends('layouts.app', ['title' => 'Cliente · Alfarei CNC'])

@section('content')
<div class="heading"><div><p class="eyebrow">CADASTROS · CLIENTES</p><h1>{{ $customer->exists ? 'Editar cliente' : 'Novo cliente' }}</h1><p class="muted">Dados comerciais e acesso à loja virtual.</p></div><a class="secondary" href="{{ route('clientes.index') }}">← Voltar</a></div>
<form class="panel form-card" method="POST" action="{{ $customer->exists ? route('clientes.update', $customer) : route('clientes.store') }}">
    @csrf @if($customer->exists) @method('PUT') @endif
    <div class="form-section"><h2>Informações principais</h2><div class="form-grid">
        <label>Tipo<select name="type"><option value="PF" @selected(old('type', $customer->type) === 'PF')>Pessoa física</option><option value="PJ" @selected(old('type', $customer->type) === 'PJ')>Pessoa jurídica</option></select></label>
        <label>Grupo<select name="customer_group"><option value="final" @selected(old('customer_group', $customer->customer_group) === 'final')>Cliente final</option><option value="reseller" @selected(old('customer_group', $customer->customer_group) === 'reseller')>Revendedor</option><option value="wholesale" @selected(old('customer_group', $customer->customer_group) === 'wholesale')>Atacado</option></select></label>
        <label class="span-2">Nome / Razão social<input name="name" value="{{ old('name', $customer->name) }}" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label>CPF / CNPJ<input name="document" value="{{ old('document', $customer->document) }}">@error('document')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label>E-mail<input type="email" name="email" value="{{ old('email', $customer->email) }}">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
        <label>Telefone / WhatsApp<input name="phone" value="{{ old('phone', $customer->phone) }}"></label>
        <label>Cidade<input name="city" value="{{ old('city', $customer->city) }}"></label>
        <label>UF<input name="state" maxlength="2" value="{{ old('state', $customer->state) }}"></label>
        <label class="span-2">Observações<textarea name="notes" rows="4">{{ old('notes', $customer->notes) }}</textarea></label>
    </div></div>
    <div class="form-section"><h2>Acesso à loja virtual</h2><p class="muted">O cliente entra com o e-mail acima e a senha definida aqui. Deixe em branco para manter a senha atual ou não liberar o acesso.</p><div class="form-grid"><label class="span-2">{{ $customer->exists ? 'Nova senha da loja (opcional)' : 'Senha inicial da loja (opcional)' }}<input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Mínimo de 8 caracteres">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label></div></div>
    <label class="check"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $customer->exists ? $customer->active : true))> Cliente ativo</label>
    <div class="form-actions"><a class="secondary" href="{{ route('clientes.index') }}">Cancelar</a><button class="primary" type="submit">Salvar cliente →</button></div>
</form>
@endsection
