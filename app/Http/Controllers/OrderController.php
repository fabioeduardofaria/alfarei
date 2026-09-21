<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\FinanceEntry;
use App\Services\ProductionWorkflowService;
use App\Services\CustomerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly ProductionWorkflowService $workflow,
        private readonly CustomerNotificationService $notifications,
    ) {}
    public function index(): View
    {
        return view('orders.index', ['orders' => Order::with('customer')->latest()->paginate(12)]);
    }

    public function show(Order $pedido): View
    {
        return view('orders.show', ['order' => $pedido->load('customer', 'items', 'payments')]);
    }

    public function confirmDeposit(Order $pedido): RedirectResponse
    {
        if ($pedido->status !== 'awaiting_deposit') return back()->with('success', 'A entrada deste pedido já foi tratada.');
        $pedido->payments()->where('type', 'deposit')->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now(), 'method' => 'manual']);
        $payment = $pedido->payments()->where('type', 'deposit')->first();
        FinanceEntry::where('source_type', 'payment')->where('source_id', $payment?->id)->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now(), 'payment_method' => 'manual']);
        $needsArt = $pedido->items()->where('made_to_order', true)->exists();
        $pedido->update(['deposit_paid_at' => now(), 'status' => $needsArt ? 'awaiting_art' : 'ready_for_production']);
        return back()->with('success', $needsArt ? 'Entrada confirmada. O pedido aguarda aprovação de arte.' : 'Entrada confirmada. Pedido liberado para produção.');
    }

    public function approveArt(Order $pedido): RedirectResponse
    {
        if ($pedido->status !== 'awaiting_art') return back()->with('success', 'A arte deste pedido já foi tratada.');
        $pedido->update(['art_approved_at' => now(), 'status' => 'ready_for_production']);
        return back()->with('success', 'Arte aprovada. Pedido liberado para criação da Ordem de Produção.');
    }

    public function createProductionOrder(Order $pedido): RedirectResponse
    {
        if ($pedido->status !== 'ready_for_production') return back()->withErrors(['status' => 'Este pedido ainda não está liberado para produção.']);
        $production = $this->workflow->createFromOrder($pedido, request()->user()->id);
        return redirect()->route('producao.show', $production)->with('success', 'Ordem de Produção '.$production->number.' criada.');
    }
    public function ship(Request $request, Order $pedido): RedirectResponse
    {
        $data = $request->validate(['tracking_code' => ['nullable', 'string', 'max:100']]);
        if (! in_array($pedido->status, ['ready', 'quality'], true)) return back()->withErrors(['status' => 'O pedido precisa estar pronto para ser despachado.']);
        $pedido->update(['status' => 'delivered', 'tracking_code' => $data['tracking_code'] ?? null, 'shipped_at' => now(), 'delivered_at' => $pedido->delivery_method === 'pickup' ? now() : null]);
        $this->notifications->queue($pedido->fresh(), 'shipped');
        return back()->with('success', $pedido->delivery_method === 'pickup' ? 'Retirada registrada.' : 'Despacho registrado.');
    }
}
