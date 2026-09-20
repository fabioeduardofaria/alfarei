<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\Order;
use App\Services\ProductionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(private readonly ProductionWorkflowService $workflow) {}
    public function index(): View { return view('production.index', ['orders' => ProductionOrder::with('order.customer', 'machine')->latest()->paginate(12), 'readyOrders' => Order::with('customer')->where('status', 'ready_for_production')->latest()->get()]); }
    public function show(ProductionOrder $ordemProducao): View { return view('production.show', ['production' => $ordemProducao->load('order.customer', 'order.items', 'machine', 'events.user')]); }
    public function advance(Request $request, ProductionOrder $ordemProducao): RedirectResponse
    {
        $data = $request->validate(['minutes' => ['nullable', 'integer', 'min:0', 'max:1440']]);
        $this->workflow->advance($ordemProducao, $request->user()->id, $data['minutes'] ?? 0);
        return back()->with('success', 'Etapa atualizada com sucesso.');
    }
    public function logOccurrence(Request $request, ProductionOrder $ordemProducao): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', 'in:loss,rework,note'], 'minutes' => ['nullable', 'integer', 'min:0'], 'loss_cost' => ['nullable', 'numeric', 'min:0'], 'rework_cost' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $ordemProducao->events()->create(array_merge($data, ['user_id' => $request->user()->id]));
        $ordemProducao->increment('actual_minutes', $data['minutes'] ?? 0);
        $ordemProducao->increment('material_loss_cost', $data['loss_cost'] ?? 0);
        $ordemProducao->increment('rework_cost', $data['rework_cost'] ?? 0);
        return back()->with('success', 'Ocorrência registrada na OP.');
    }
}
