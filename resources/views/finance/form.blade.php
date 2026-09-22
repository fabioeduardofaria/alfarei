@extends('layouts.app', ['title' => ($entry->exists ? 'Editar' : 'Nova').' conta · Alfarei CNC'])
@section('content')
<div class="heading"><div><p class="eyebrow">FINANCEIRO · CONTA MANUAL</p><h1>{{ $entry->exists ? 'Editar conta' : 'Nova conta' }}</h1><p class="muted">Use este formulário para aluguel, energia, serviços, vendas avulsas e outras contas fora de pedidos e compras.</p></div><a class="secondary" href="{{ $entry->exists ? route('financeiro.show', $entry) : route('financeiro.index') }}">← Voltar</a></div>
@if($errors->any())<div class="flash error"><b>Confira os dados:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="panel finance-form" method="POST" action="{{ $entry->exists ? route('financeiro.update', $entry) : route('financeiro.store') }}">
    @csrf @if($entry->exists) @method('PUT') @endif
    <div class="finance-form-section"><h2>1. Identificação</h2><div class="form-grid">
        <label>Natureza<select name="type" required @disabled($entry->exists)><option value="payable" @selected(old('type', $entry->type ?? request('tipo', 'payable')) === 'payable')>Conta a pagar</option><option value="receivable" @selected(old('type', $entry->type ?? request('tipo')) === 'receivable')>Conta a receber</option></select>@if($entry->exists)<input type="hidden" name="type" value="{{ $entry->type }}">@endif</label>
        <label>Categoria<input name="category" list="finance-categories" value="{{ old('category', $entry->category) }}" placeholder="Ex.: Aluguel" required></label>
        <datalist id="finance-categories"><option value="Materiais"><option value="Aluguel"><option value="Energia"><option value="Internet"><option value="Impostos"><option value="Folha de pagamento"><option value="Frete"><option value="Serviços"><option value="Vendas"><option value="Outros"></datalist>
        <label class="span-2">Descrição<input name="description" maxlength="180" value="{{ old('description', $entry->description) }}" placeholder="Ex.: Aluguel da oficina" required></label>
        <label class="partner-payable">Fornecedor (opcional)<select name="supplier_id"><option value="">Não vincular</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id', $entry->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
        <label class="partner-receivable">Cliente (opcional)<select name="customer_id"><option value="">Não vincular</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id', $entry->customer_id) == $customer->id)>{{ $customer->name }}</option>@endforeach</select></label>
        <label>Nome avulso (opcional)<input name="counterparty" maxlength="150" value="{{ old('counterparty', $entry->counterparty) }}" placeholder="Quando não houver cadastro"></label>
        <label>Número do documento (opcional)<input name="document_number" maxlength="80" value="{{ old('document_number', $entry->document_number) }}" placeholder="Nota, boleto ou contrato"></label>
    </div></div>
    <div class="finance-form-section"><h2>2. Valor e vencimento</h2><div class="form-grid">
        <label>{{ $entry->exists ? 'Valor desta conta (R$)' : 'Valor total (R$)' }}<input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount', $entry->amount) }}" required></label>
        <label>{{ $entry->exists ? 'Vencimento' : 'Primeiro vencimento' }}<input type="date" name="due_date" value="{{ old('due_date', $entry->due_date?->format('Y-m-d') ?? today()->toDateString()) }}" required></label>
        @unless($entry->exists)<label>Parcelas mensais<select name="installments">@foreach([1,2,3,4,5,6,8,10,12,18,24,36] as $count)<option value="{{ $count }}" @selected(old('installments', 1) == $count)>{{ $count }} {{ $count === 1 ? 'parcela' : 'parcelas' }}</option>@endforeach</select></label>@endunless
    </div><p class="muted">{{ $entry->exists ? 'Esta edição altera apenas a parcela atual.' : 'As parcelas serão distribuídas em centavos, com vencimentos mensais a partir da data informada.' }}</p></div>
    <div class="finance-form-section"><label class="finance-notes">Observações<textarea name="notes" rows="3" placeholder="Informações úteis para a conferência">{{ old('notes', $entry->notes) }}</textarea></label></div>
    <div class="form-actions"><a class="secondary" href="{{ $entry->exists ? route('financeiro.show', $entry) : route('financeiro.index') }}">Cancelar</a><button class="primary">{{ $entry->exists ? 'Salvar alterações' : 'Criar conta' }} →</button></div>
</form>
<script>const financeType=document.querySelector('.finance-form select[name="type"]');function syncFinancePartner(){document.querySelector('.partner-payable').hidden=financeType.value!=='payable';document.querySelector('.partner-receivable').hidden=financeType.value!=='receivable'}financeType.addEventListener('change',syncFinancePartner);syncFinancePartner();</script>
@endsection
