<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(): View
    {
        return view('machines.index', ['machines' => Machine::with('maintenances')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('machines.form', ['machine' => new Machine]);
    }

    public function store(Request $request): RedirectResponse
    {
        Machine::create($this->data($request));

        return redirect()->route('maquinas.index')->with('success', 'Máquina cadastrada.');
    }

    public function edit(Machine $maquina): View
    {
        return view('machines.form', ['machine' => $maquina]);
    }

    public function update(Request $request, Machine $maquina): RedirectResponse
    {
        $maquina->update($this->data($request));

        return redirect()->route('maquinas.index')->with('success', 'Máquina atualizada.');
    }

    public function maintenances(Request $request, Machine $maquina): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->endOfMonth()->toDateString();
        $maintenances = $maquina->maintenances()
            ->whereBetween('scheduled_for', [$from, $to])
            ->latest('scheduled_for')
            ->get();

        return view('machines.maintenances', [
            'machine' => $maquina,
            'maintenances' => $maintenances,
            'from' => $from,
            'to' => $to,
            'summary' => [
                'total' => $maintenances->sum('cost'),
                'preventive' => $maintenances->where('type', 'preventive')->sum('cost'),
                'corrective' => $maintenances->where('type', 'corrective')->sum('cost'),
                'count' => $maintenances->count(),
            ],
        ]);
    }

    public function storeMaintenance(Request $request, Machine $maquina): RedirectResponse
    {
        $maintenance = $maquina->maintenances()->create($this->maintenanceData($request));

        $maquina->update(['next_maintenance_at' => $maintenance->scheduled_for]);

        return back()->with('success', 'Manutenção agendada com sucesso.');
    }

    public function completeMaintenance(Machine $maquina, MachineMaintenance $maintenance): RedirectResponse
    {
        abort_unless($maintenance->machine_id === $maquina->id, 404);

        $maintenance->update(['status' => 'completed', 'completed_at' => now()]);
        $nextMaintenance = $maquina->maintenances()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->orderBy('scheduled_for')
            ->value('scheduled_for');
        $maquina->update(['next_maintenance_at' => $nextMaintenance]);

        return back()->with('success', 'Manutenção concluída e custo registrado.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:30'], 'type' => ['required', 'string', 'max:50'], 'acquisition_value' => ['nullable', 'numeric', 'min:0'], 'residual_value' => ['nullable', 'numeric', 'min:0'], 'useful_life_months' => ['nullable', 'integer', 'min:1'], 'planned_hours_month' => ['nullable', 'integer', 'min:1'], 'power_kw' => ['nullable', 'numeric', 'min:0'], 'energy_rate' => ['nullable', 'numeric', 'min:0'], 'labor_cost_hour' => ['nullable', 'numeric', 'min:0'], 'maintenance_cost_hour' => ['nullable', 'numeric', 'min:0'], 'consumables_cost_hour' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:available,maintenance,unavailable']]);
        $data['active'] = $request->boolean('active');

        return $data;
    }

    private function maintenanceData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:preventive,corrective'],
            'scheduled_for' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'machine_hours' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
