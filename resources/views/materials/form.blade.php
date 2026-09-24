@extends('layouts.app', ['title' => ($material->exists ? 'Editar material' : 'Novo material').' · Alfarei CNC'])
@section('content')
@php($selectedCategory = old('category_id', $categories->firstWhere('name', $material->category)?->id))
<div class="heading">
    <div><p class="eyebrow">CADASTROS · MATERIAIS</p><h1>{{ $material->exists ? 'Editar material' : 'Novo material' }}</h1><p class="muted">Esses dados serão usados na formação de custo e na reserva de estoque.</p></div>
    <a class="secondary" href="{{ route('materiais.index') }}">← Voltar</a>
</div>
<form class="panel form-card" method="POST" action="{{ $material->exists ? route('materiais.update', $material) : route('materiais.store') }}">
    @csrf
    @if($material->exists) @method('PUT') @endif
    <div class="form-section">
        <h2>Ficha do material</h2>
        <div class="form-grid">
            <label>Código<input name="code" value="{{ old('code', $material->code) }}" required>@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <div class="material-category-field">
                <label for="materialCategory">Categoria</label>
                <div class="material-category-picker">
                    <select id="materialCategory" name="category_id" required>
                        <option value="">Selecione a categoria</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button class="secondary" type="button" id="addMaterialCategory" aria-expanded="false" aria-controls="newMaterialCategory">+ Nova</button>
                    <button class="secondary" type="button" id="editMaterialCategory" aria-expanded="false" aria-controls="editMaterialCategoryPanel" @disabled(!$selectedCategory)>Editar</button>
                </div>
                <p class="material-category-help">Use a mesma categoria para materiais do mesmo grupo.</p>
                @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
                <div class="material-category-create" id="newMaterialCategory" data-url="{{ route('materiais.categories.store') }}" hidden>
                    <label for="newMaterialCategoryName">Nome da nova categoria</label>
                    <div class="material-category-picker">
                        <input id="newMaterialCategoryName" maxlength="80" placeholder="Ex.: Adesivo" autocomplete="off">
                        <button class="primary" type="button" id="saveMaterialCategory">Salvar</button>
                    </div>
                    <p class="material-category-status" id="materialCategoryStatus" role="status" aria-live="polite"></p>
                </div>
                <div class="material-category-create" id="editMaterialCategoryPanel" data-url-template="{{ route('materiais.categories.update', ['category' => '__CATEGORY__']) }}" hidden>
                    <label for="editMaterialCategoryName">Corrigir nome da categoria</label>
                    <p class="material-category-help">A correção atualiza todos os materiais desta categoria.</p>
                    <div class="material-category-picker">
                        <input id="editMaterialCategoryName" maxlength="80" autocomplete="off">
                        <button class="primary" type="button" id="saveEditedMaterialCategory">Salvar correção</button>
                    </div>
                    <p class="material-category-status" id="editMaterialCategoryStatus" role="status" aria-live="polite"></p>
                </div>
            </div>
            <label class="span-2">Descrição<input name="name" value="{{ old('name', $material->name) }}" required></label>
            <label>Unidade<select name="unit"><option value="chapa" @selected(old('unit', $material->unit) === 'chapa')>Chapa</option><option value="un" @selected(old('unit', $material->unit) === 'un')>Unidade</option><option value="m²" @selected(old('unit', $material->unit) === 'm²')>m²</option><option value="kg" @selected(old('unit', $material->unit) === 'kg')>kg</option><option value="l" @selected(old('unit', $material->unit) === 'l')>Litro</option></select></label>
            <label>Espessura (mm)<input type="number" step="0.01" min="0" name="thickness_mm" value="{{ old('thickness_mm', $material->thickness_mm) }}"></label>
            <label>Largura (mm)<input type="number" step="0.01" min="0" name="width_mm" value="{{ old('width_mm', $material->width_mm) }}"></label>
            <label>Altura (mm)<input type="number" step="0.01" min="0" name="height_mm" value="{{ old('height_mm', $material->height_mm) }}"></label>
            <label>Custo por unidade<input type="number" step="0.01" min="0" name="cost_per_unit" value="{{ old('cost_per_unit', $material->cost_per_unit ?? 0) }}" required></label>
            <label>Estoque atual<input type="number" step="0.001" min="0" name="stock_quantity" value="{{ old('stock_quantity', $material->stock_quantity ?? 0) }}" required></label>
            <label>Estoque mínimo<input type="number" step="0.001" min="0" name="minimum_stock" value="{{ old('minimum_stock', $material->minimum_stock ?? 0) }}" required></label>
            <label>Localização<input name="location" value="{{ old('location', $material->location) }}" placeholder="Ex.: A-02"></label>
        </div>
    </div>
    <label class="check"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $material->exists ? $material->active : true))> Material ativo</label>
    <div class="form-actions"><a class="secondary" href="{{ route('materiais.index') }}">Cancelar</a><button class="primary" type="submit">Salvar material →</button></div>
</form>
<script src="{{ asset('js/material-categories.js') }}?v={{ filemtime(public_path('js/material-categories.js')) }}" defer></script>
@endsection
