@extends('layouts.app', ['title' => 'Manutenção · Alfarei CNC'])

@section('content')
    <div class="heading">
        <div>
            <p class="eyebrow">PRODUÇÃO · MANUTENÇÃO</p>
            <h1>{{ $machine->name }}</h1>
            <p class="muted">Agenda preventiva e corretiva · {{ $machine->code }}</p>
        </div>
        <a class="secondary" href="{{ route('maquinas.index') }}">← Máquinas</a>
    </div>

    <form class="panel maintenance-filter" method="GET">
        <label>De
            <input type="date" name="from" value="{{ $from }}">
        </label>
        <label>Até
            <input type="date" name="to" value="{{ $to }}">
        </label>
        <button class="secondary">Aplicar período</button>
    </form>

    <section class="maintenance-summary">
        <article><span>CUSTO NO PERÍODO</span><b>R$ {{ number_format($summary['total'], 2, ',', '.') }}</b><small>{{ $summary['count'] }} manutenção(ões)</small></article>
        <article><span>PREVENTIVA</span><b>R$ {{ number_format($summary['preventive'], 2, ',', '.') }}</b><small>Planejamento e prevenção</small></article>
        <article><span>CORRETIVA</span><b>R$ {{ number_format($summary['corrective'], 2, ',', '.') }}</b><small>Reparos e paradas</small></article>
    </section>

    <form class="panel form-card" method="POST" action="{{ route('maquinas.maintenances.store', $machine) }}">
        @csrf
        <div class="form-section">
            <h2>Agendar manutenção</h2>
            <div class="form-grid">
                <label>Tipo
                    <select name="type" required>
                        <option value="preventive" @selected(old('type') === 'preventive')>Preventiva</option>
                        <option value="corrective" @selected(old('type') === 'corrective')>Corretiva</option>
                    </select>
                </label>
                <label>Data programada
                    <input type="date" name="scheduled_for" value="{{ old('scheduled_for', now()->addMonth()->toDateString()) }}" required>
                </label>
                <label>Custo previsto (R$)
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', 0) }}">
                </label>
                <label>Horímetro da máquina
                    <input type="number" min="0" name="machine_hours" value="{{ old('machine_hours', 0) }}">
                </label>
                <label class="span-2">Serviço / observações
                    <textarea name="description" rows="3" placeholder="Ex.: limpeza de lentes, alinhamento, troca de correia.">{{ old('description') }}</textarea>
                </label>
            </div>
        </div>
        <div class="form-actions"><button class="primary">Agendar manutenção</button></div>
    </form>

    <article class="panel table-panel">
        <table>
            <thead><tr><th>TIPO</th><th>DATA</th><th>HORÍMETRO</th><th>CUSTO</th><th>SERVIÇO</th><th>STATUS</th><th></th></tr></thead>
            <tbody>
                @forelse($maintenances as $maintenance)
                    <tr>
                        <td>{{ $maintenance->type === 'preventive' ? 'Preventiva' : 'Corretiva' }}</td>
                        <td>{{ $maintenance->scheduled_for?->format('d/m/Y') }}</td>
                        <td>{{ number_format($maintenance->machine_hours, 0, ',', '.') }} h</td>
                        <td>R$ {{ number_format($maintenance->cost, 2, ',', '.') }}</td>
                        <td>{{ $maintenance->description ?: '—' }}</td>
                        <td>{{ $maintenance->status === 'completed' ? 'Concluída em '.$maintenance->completed_at?->format('d/m/Y') : 'Agendada' }}</td>
                        <td>
                            @if($maintenance->status === 'scheduled')
                                <form method="POST" action="{{ route('maquinas.maintenances.complete', [$machine, $maintenance]) }}">@csrf<button class="secondary">Concluir</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Nenhuma manutenção registrada para esta máquina.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
@endsection
