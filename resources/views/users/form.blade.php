@extends('layouts.app', ['title' => ($user->exists ? 'Editar usuário' : 'Novo usuário').' · Alfarei CNC'])

@section('content')
<div class="heading"><div><p class="eyebrow">ADMINISTRAÇÃO · ACESSOS</p><h1>{{ $user->exists ? 'Editar usuário' : 'Novo usuário' }}</h1><p class="muted">O perfil sugere acessos iniciais; os módulos marcados abaixo são os que valerão para esta conta.</p></div><a class="secondary" href="{{ route('usuarios.index') }}">← Usuários</a></div>
<form class="panel form-card user-form" method="POST" action="{{ $user->exists ? route('usuarios.update', $user) : route('usuarios.store') }}">
    @csrf
    @if($user->exists) @method('PUT') @endif
    <section class="form-section">
        <h2>Dados da conta</h2>
        <div class="form-grid">
            <label>Nome<input name="name" value="{{ old('name', $user->name) }}" required></label>
            <label>E-mail<input name="email" type="email" value="{{ old('email', $user->email) }}" required></label>
            <label>Perfil<select name="role" required>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(old('role', $user->role ?: 'commercial') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>{{ $user->exists ? 'Nova senha (opcional)' : 'Senha inicial' }}<input name="password" type="password" {{ $user->exists ? '' : 'required' }} minlength="8" placeholder="Mínimo de 8 caracteres"></label>
            <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->exists ? $user->active : true))> Conta ativa e autorizada a entrar</label>
        </div>
    </section>
    <section class="form-section permission-section">
        <h2>Módulos permitidos</h2>
        <p class="muted">Administradores têm acesso total, independentemente desta seleção.</p>
        <div class="permission-grid">
            @foreach($modules as $module => $label)
                <label class="permission-check"><input type="checkbox" name="permissions[]" value="{{ $module }}" @checked(in_array($module, old('permissions', $selectedPermissions), true))><span>{{ $label }}</span></label>
            @endforeach
        </div>
    </section>
    <div class="form-actions"><a class="secondary" href="{{ route('usuarios.index') }}">Cancelar</a><button class="primary" type="submit">Salvar usuário</button></div>
</form>
@endsection
