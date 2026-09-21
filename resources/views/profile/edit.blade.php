@extends('layouts.app', ['title' => 'Minha conta · Alfarei CNC'])

@section('content')
<div class="heading"><div><p class="eyebrow">MINHA CONTA</p><h1>Dados e segurança</h1><p class="muted">Atualize seus dados de acesso. Seu perfil e permissões são definidos pelo administrador.</p></div></div>
<form class="panel form-card" method="POST" action="{{ route('perfil.update') }}">
    @csrf @method('PUT')
    <section class="form-section"><h2>Identificação</h2><div class="form-grid"><label>Nome<input name="name" value="{{ old('name', $user->name) }}" required></label><label>E-mail<input name="email" type="email" value="{{ old('email', $user->email) }}" required></label></div></section>
    <section class="form-section"><h2>Alterar senha</h2><div class="form-grid"><label>Senha atual<input name="current_password" type="password"></label><label>Nova senha<input name="password" type="password" minlength="8"></label><label>Confirmar nova senha<input name="password_confirmation" type="password" minlength="8"></label></div></section>
    <div class="form-actions"><button class="primary" type="submit">Salvar minha conta</button></div>
</form>
@endsection
