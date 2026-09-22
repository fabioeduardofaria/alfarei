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
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(private readonly QuotePricingService $pricing, private readonly QuoteToOrderService $orderService) {}

    public function index(Request $request): View
    {
        Quote::whereIn('status', ['draft', 'sent', 'negotiation'])
            ->whereDate('valid_until', '<', today())
            ->update(['status' => 'expired']);
        $quotes = Quote::with('customer')->latest();
        if ($status = $request->string('status')->trim()->toString()) {
            $quotes->where('status', $status);
        }

        return view('quotes.index', ['quotes' => $quotes->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->form(new Quote(['status' => 'draft', 'valid_until' => now()->addDays(7)]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = DB::transaction(function () use ($data, $request) {
            $calculated = $this->pricing->calculate($data['items'], (float) $data['discount']);
            $number = sprintf('ORC-%s-%04d', now()->format('Y'), Quote::count() + 1);
            $quote = Quote::create(array_merge(collect($data)->except('items')->all(), $calculated, ['number' => $number, 'version' => 1, 'created_by' => $request->user()->id, 'approval_token' => $data['status'] === 'sent' ? Str::random(60) : null, 'sent_at' => $data['status'] === 'sent' ? now() : null]));
            $quote->items()->createMany($calculated['items']);

            return $quote;
        });

        return redirect()->route('orcamentos.edit', $quote)->with('success', 'Orçamento '.$quote->number.' criado com sucesso.');
    }

    public function edit(Quote $orcamento): View
    {
        return $this->form($orcamento->load('items'));
    }

    public function update(Request $request, Quote $orcamento): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $orcamento) {
            $calculated = $this->pricing->calculate($data['items'], (float) $data['discount']);
            $orcamento->update(array_merge(collect($data)->except('items')->all(), $calculated, ['approval_token' => $data['status'] === 'sent' ? ($orcamento->approval_token ?: Str::random(60)) : $orcamento->approval_token, 'sent_at' => $data['status'] === 'sent' ? ($orcamento->sent_at ?: now()) : $orcamento->sent_at]));
            $orcamento->items()->delete();
            $orcamento->items()->createMany($calculated['items']);
        });

        return redirect()->route('orcamentos.edit', $orcamento)->with('success', 'Orçamento atualizado.');
    }

    public function convertToOrder(Quote $orcamento, Request $request): RedirectResponse
    {
        if ($orcamento->status !== 'approved') {
            return back()->withErrors(['status' => 'Aprove o orçamento antes de convertê-lo em pedido.']);
        }
        $order = $this->orderService->convert($orcamento, $request->user()->id);

        return redirect()->route('pedidos.show', $order)->with('success', 'Pedido '.$order->number.' criado. Confirme a entrada para seguir.');
    }

    public function createRevision(Quote $orcamento, Request $request): RedirectResponse
    {
        $orcamento->load('items');
        $rootNumber = preg_replace('/-V\d+$/', '', $orcamento->parent?->number ?? $orcamento->number);
        $nextVersion = Quote::where('number', 'like', $rootNumber.'%')->max('version') + 1;
        $revision = DB::transaction(function () use ($orcamento, $request, $rootNumber, $nextVersion) {
            $revision = Quote::create($orcamento->only(['customer_id', 'valid_until', 'notes', 'payment_terms', 'production_lead_days', 'deposit_percent', 'discount', 'subtotal', 'cost_total', 'total']) + [
                'number' => $rootNumber.'-V'.$nextVersion, 'parent_quote_id' => $orcamento->parent_quote_id ?: $orcamento->id,
                'created_by' => $request->user()->id, 'version' => $nextVersion, 'status' => 'draft',
            ]);
            $revision->items()->createMany($orcamento->items->map(fn ($item) => $item->only(['product_id', 'description', 'type', 'quantity', 'unit_price', 'unit_cost', 'total', 'total_cost']))->all());

            return $revision;
        });

        return redirect()->route('orcamentos.edit', $revision)->with('success', 'Nova versão criada sem alterar o histórico anterior.');
    }

    private function form(Quote $quote): View
    {
        return view('quotes.form', ['quote' => $quote, 'customers' => Customer::where('active', true)->orderBy('name')->get(), 'products' => Product::where('active', true)->orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        $request->mergeIfMissing(['deposit_percent' => 50]);

        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'], 'status' => ['required', 'in:draft,sent,negotiation,approved,rejected,expired,cancelled'],
            'valid_until' => ['nullable', 'date'], 'discount' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:3000'], 'payment_terms' => ['nullable', 'string', 'max:3000'], 'production_lead_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'deposit_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:200'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
