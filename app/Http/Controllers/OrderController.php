<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\Order;
use App\Services\CustomerNotificationService;
use App\Services\ProductionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if ($pedido->status !== 'awaiting_deposit') {
            return back()->with('success', 'A entrada deste pedido já foi tratada.');
        }
        $nextStatus = DB::transaction(function () use ($pedido) {
            $order = Order::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'awaiting_deposit') {
                return null;
            }
            $payment = $order->payments()->where('type', 'deposit')->first();
            abort_unless($payment, 409, 'Não há cobrança de entrada vinculada a este pedido.');
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'method' => 'manual']);
            $entry = FinanceEntry::where('source_type', 'payment')->where('source_id', $payment?->id)->lockForUpdate()->first();
            if ($entry && in_array($entry->status, ['pending', 'partial'], true)) {
                if ($entry->remaining_amount > 0) {
                    $entry->payments()->create(['created_by' => request()->user()->id, 'amount' => $entry->remaining_amount, 'paid_at' => today(), 'payment_method' => 'manual']);
                }
                $entry->update(['status' => 'paid', 'paid_amount' => $entry->amount, 'paid_at' => now(), 'payment_method' => 'manual']);
            }
            $onlyDigital = $order->items()->where('type', 'virtual')->exists() && ! $order->items()->where('type', '!=', 'virtual')->exists();
            $needsArt = $order->items()->where('made_to_order', true)->exists();
            $status = $onlyDigital ? 'delivered' : ($needsArt ? 'awaiting_art' : 'ready_for_production');
            $order->update(['deposit_paid_at' => now(), 'status' => $status, 'delivered_at' => $onlyDigital ? now() : null]);

            return $status;
        });
        if ($nextStatus === null) {
            return back()->with('success', 'A entrada deste pedido já foi tratada.');
        }

        if ($pedido->items()->where('type', 'virtual')->exists() && $pedido->fresh()->digitalDownloadReady()) {
            $this->notifications->queue($pedido, 'digital_ready');
        }

        if ($nextStatus === 'delivered') {
            return back()->with('success', 'Pagamento integral confirmado. Arquivos disponíveis na conta do cliente.');
        }

        return back()->with('success', $nextStatus === 'awaiting_art' ? 'Entrada confirmada. O pedido aguarda aprovação de arte.' : 'Entrada confirmada. Pedido liberado para produção.');
    }

    public function approveArt(Order $pedido): RedirectResponse
    {
        if ($pedido->status !== 'awaiting_art') {
            return back()->with('success', 'A arte deste pedido já foi tratada.');
        }
        $pedido->update(['art_approved_at' => now(), 'status' => 'ready_for_production']);

        return back()->with('success', 'Arte aprovada. Pedido liberado para criação da Ordem de Produção.');
    }

    public function createProductionOrder(Order $pedido): RedirectResponse
    {
        if ($pedido->status !== 'ready_for_production') {
            return back()->withErrors(['status' => 'Este pedido ainda não está liberado para produção.']);
        }
        $production = $this->workflow->createFromOrder($pedido, request()->user()->id);

        return redirect()->route('producao.show', $production)->with('success', 'Ordem de Produção '.$production->number.' criada.');
    }

    public function ship(Request $request, Order $pedido): RedirectResponse
    {
        $data = $request->validate(['tracking_code' => ['nullable', 'string', 'max:100']]);
        if (! in_array($pedido->status, ['ready', 'quality'], true)) {
            return back()->withErrors(['status' => 'O pedido precisa estar pronto para ser despachado.']);
        }
        $pedido->update(['status' => 'delivered', 'tracking_code' => $data['tracking_code'] ?? null, 'shipped_at' => now(), 'delivered_at' => $pedido->delivery_method === 'pickup' ? now() : null]);
        $this->notifications->queue($pedido->fresh(), 'shipped');

        return back()->with('success', $pedido->delivery_method === 'pickup' ? 'Retirada registrada.' : 'Despacho registrado.');
    }
}
