<?php

namespace App\Http\Controllers;

use App\Models\Machine;
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

    private function data(Request $request): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:30'], 'type' => ['required', 'string', 'max:50'], 'acquisition_value' => ['nullable', 'numeric', 'min:0'], 'residual_value' => ['nullable', 'numeric', 'min:0'], 'useful_life_months' => ['nullable', 'integer', 'min:1'], 'planned_hours_month' => ['nullable', 'integer', 'min:1'], 'power_kw' => ['nullable', 'numeric', 'min:0'], 'energy_rate' => ['nullable', 'numeric', 'min:0'], 'labor_cost_hour' => ['nullable', 'numeric', 'min:0'], 'maintenance_cost_hour' => ['nullable', 'numeric', 'min:0'], 'consumables_cost_hour' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'in:available,maintenance,unavailable']]);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
