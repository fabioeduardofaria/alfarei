<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\FinanceEntry;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        return view('purchases.index', [
            'purchases' => PurchaseOrder::with('supplier')->latest()->paginate(12),
            'lowMaterials' => Material::where('active', true)->whereRaw('(stock_quantity - reserved_quantity) <= minimum_stock')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('purchases.form', ['purchase' => new PurchaseOrder(), 'suppliers' => Supplier::where('active', true)->orderBy('name')->get(), 'materials' => Material::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $purchase = DB::transaction(function () use ($data, $request) {
            $total = collect($data['items'])->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_cost']);
            $purchase = PurchaseOrder::create(['number' => sprintf('PC-%s-%04d', now()->format('Y'), PurchaseOrder::count() + 1), 'supplier_id' => $data['supplier_id'], 'created_by' => $request->user()->id, 'status' => $data['status'], 'expected_at' => $data['expected_at'] ?? null, 'notes' => $data['notes'] ?? null, 'total' => $total]);
            foreach ($data['items'] as $item) $purchase->items()->create(['material_id' => $item['material_id'], 'quantity' => $item['quantity'], 'unit_cost' => $item['unit_cost'], 'total' => (float) $item['quantity'] * (float) $item['unit_cost']]);
            $supplier = Supplier::findOrFail($data['supplier_id']);
            FinanceEntry::create(['type' => 'payable', 'source_type' => 'purchase', 'source_id' => $purchase->id, 'description' => 'Compra '.$purchase->number, 'counterparty' => $supplier->name, 'due_date' => $data['expected_at'] ?? now()->addDays(15)->toDateString(), 'amount' => $total]);
            return $purchase;
        });
        return redirect()->route('compras.show', $purchase)->with('success', 'Pedido de compra criado.');
    }

    public function show(PurchaseOrder $compra): View { return view('purchases.show', ['purchase' => $compra->load('supplier', 'items.material')]); }

    public function receive(Request $request, PurchaseOrder $compra): RedirectResponse
    {
        if ($compra->status === 'received') return back()->with('success', 'Esta compra já foi recebida no estoque.');
        DB::transaction(function () use ($compra, $request) {
            $purchase = PurchaseOrder::with('items.material')->lockForUpdate()->findOrFail($compra->id);
            foreach ($purchase->items as $item) {
                $material = Material::lockForUpdate()->findOrFail($item->material_id);
                $material->increment('stock_quantity', $item->quantity);
                $material->update(['cost_per_unit' => $item->unit_cost]);
                InventoryMovement::create(['material_id' => $material->id, 'type' => 'purchase', 'status' => 'posted', 'quantity' => $item->quantity, 'unit_cost' => $item->unit_cost, 'notes' => 'Entrada pelo pedido de compra '.$purchase->number]);
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
        });
        return back()->with('success', 'Recebimento registrado e estoque atualizado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['supplier_id' => ['required', 'exists:suppliers,id'], 'status' => ['required', 'in:draft,ordered'], 'expected_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array', 'min:1'], 'items.*.material_id' => ['required', 'distinct', 'exists:materials,id'], 'items.*.quantity' => ['required', 'numeric', 'min:0.001'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0']]);
    }
}
