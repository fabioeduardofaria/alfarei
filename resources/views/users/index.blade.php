@extends('layouts.app', ['title' => 'Usuários · Alfarei CNC'])

@section('content')
<div class="heading">
    <div><p class="eyebrow">ADMINISTRAÇÃO</p><h1>Usuários e acessos</h1><p class="muted">Defina perfis e libere somente os módulos necessários para cada pessoa.</p></div>
    <a class="primary" href="{{ route('usuarios.create') }}">+ Novo usuário</a>
</div>
<article class="panel table-panel">
    <table>
        <thead><tr><th>USUÁRIO</th><th>PERFIL</th><th>ACESSOS</th><th>ÚLTIMO ACESSO</th><th>STATUS</th><th class="actions-header">AÇÃO</th></tr></thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td><b>{{ $user->name }}</b><small>{{ $user->email }}</small></td>
                <td>{{ $roles[$user->role] ?? $user->role }}</td>
                <td><small>{{ count($user->effectivePermissions()) }} módulos liberados</small></td>
                <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Ainda não acessou' }}</td>
                <td><span class="status {{ $user->active ? 'on' : 'off' }}">{{ $user->active ? 'ATIVO' : 'INATIVO' }}</span></td>
                <td class="actions-cell"><a class="edit" href="{{ route('usuarios.edit', $user) }}">Editar →</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Nenhum usuário cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $users->links() }}</div>
</article>
@endsection
