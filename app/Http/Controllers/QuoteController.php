<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuotePricingService;
use App\Services\QuoteToOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(private readonly QuotePricingService $pricing, private readonly QuoteToOrderService $orderService) {}

    public function index(Request $request): View
    {
        $quotes = Quote::with('customer')->latest();
        if ($status = $request->string('status')->trim()->toString()) $quotes->where('status', $status);
        return view('quotes.index', ['quotes' => $quotes->paginate(12)->withQueryString()]);
    }

    public function create(): View { return $this->form(new Quote(['status' => 'draft', 'valid_until' => now()->addDays(7)])); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = DB::transaction(function () use ($data, $request) {
            $calculated = $this->pricing->calculate($data['items'], (float) $data['discount']);
            $number = sprintf('ORC-%s-%04d', now()->format('Y'), Quote::count() + 1);
            $quote = Quote::create(array_merge(collect($data)->except('items')->all(), $calculated, ['number' => $number, 'version' => 1, 'created_by' => $request->user()->id]));
            $quote->items()->createMany($calculated['items']);
            return $quote;
        });
        return redirect()->route('orcamentos.edit', $quote)->with('success', 'Orçamento '.$quote->number.' criado com sucesso.');
    }

    public function edit(Quote $orcamento): View { return $this->form($orcamento->load('items')); }

    public function update(Request $request, Quote $orcamento): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $orcamento) {
            $calculated = $this->pricing->calculate($data['items'], (float) $data['discount']);
            $orcamento->update(array_merge(collect($data)->except('items')->all(), $calculated));
            $orcamento->items()->delete();
            $orcamento->items()->createMany($calculated['items']);
        });
        return redirect()->route('orcamentos.edit', $orcamento)->with('success', 'Orçamento atualizado.');
    }

    public function convertToOrder(Quote $orcamento, Request $request): RedirectResponse
    {
        if ($orcamento->status !== 'approved') return back()->withErrors(['status' => 'Aprove o orçamento antes de convertê-lo em pedido.']);
        $order = $this->orderService->convert($orcamento, $request->user()->id);
        return redirect()->route('pedidos.show', $order)->with('success', 'Pedido '.$order->number.' criado. Confirme a entrada para seguir.');
    }

    private function form(Quote $quote): View
    {
        return view('quotes.form', ['quote' => $quote, 'customers' => Customer::where('active', true)->orderBy('name')->get(), 'products' => Product::where('active', true)->orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'], 'status' => ['required', 'in:draft,sent,negotiation,approved,rejected,cancelled'],
            'valid_until' => ['nullable', 'date'], 'discount' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:200'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
