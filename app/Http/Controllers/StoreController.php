<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(): View { return view('store.index', ['settings' => $this->settings(), 'products' => Product::where('active', true)->where('store_visible', true)->orderBy('name')->get(), 'cartCount' => $this->cartCount()]); }
    public function product(Product $produto): View { abort_unless($produto->active && $produto->store_visible, 404); return view('store.product', ['settings' => $this->settings(), 'product' => $produto, 'cartCount' => $this->cartCount()]); }
    public function cart(): View { return view('store.cart', ['settings' => $this->settings(), 'lines' => $this->lines(), 'cartCount' => $this->cartCount()]); }
    public function add(Request $request, Product $produto): RedirectResponse
    {
        abort_unless($produto->active && $produto->store_visible, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100'], 'personalization' => ['nullable', 'string', 'max:500']]);
        $cart = session('store_cart', []); $key = $produto->id.'|'.($data['personalization'] ?? '');
        $cart[$key] = ['product_id' => $produto->id, 'quantity' => ($cart[$key]['quantity'] ?? 0) + $data['quantity'], 'personalization' => $data['personalization'] ?? null];
        session(['store_cart' => $cart]);
        return redirect()->route('loja.cart')->with('success', 'Item adicionado ao carrinho.');
    }
    public function remove(Request $request, string $key): RedirectResponse { $cart = session('store_cart', []); unset($cart[$key]); session(['store_cart' => $cart]); return back()->with('success', 'Item removido do carrinho.'); }
    public function checkout(): View { $lines = $this->lines(); abort_if($lines->isEmpty(), 404); return view('store.checkout', ['settings' => $this->settings(), 'lines' => $lines, 'cartCount' => $this->cartCount()]); }
    public function placeOrder(Request $request): RedirectResponse
    {
        $settings = $this->settings(); if (! $settings->store_open) return back()->withErrors(['store' => 'A loja está temporariamente indisponível.']);
        $lines = $this->lines(); if ($lines->isEmpty()) return redirect()->route('loja.index');
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150'], 'phone' => ['required', 'string', 'max:30'], 'city' => ['required', 'string', 'max:100'], 'state' => ['required', 'string', 'size:2'], 'delivery_method' => ['required', 'in:pickup,shipping'], 'postal_code' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:12'], 'street' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:150'], 'street_number' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:20'], 'complement' => ['nullable', 'string', 'max:100'], 'neighborhood' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:100'], 'delivery_city' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:100'], 'delivery_state' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'size:2'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $order = DB::transaction(function () use ($data, $lines) {
            $customer = Customer::firstOrCreate(['email' => $data['email']], ['type' => 'PF', 'name' => $data['name'], 'phone' => $data['phone'], 'city' => $data['city'], 'state' => strtoupper($data['state']), 'customer_group' => 'final', 'active' => true]);
            $total = $lines->sum('total'); $cost = $lines->sum('cost'); $deposit = round($total * .5, 2);
            $order = Order::create(['number' => sprintf('PED-%s-%04d', now()->format('Y'), Order::count() + 1), 'customer_id' => $customer->id, 'created_by' => User::where('active', true)->value('id') ?? 1, 'status' => 'awaiting_deposit', 'source' => 'ecommerce', 'delivery_method' => $data['delivery_method'], 'postal_code' => $data['postal_code'] ?? null, 'street' => $data['street'] ?? null, 'street_number' => $data['street_number'] ?? null, 'complement' => $data['complement'] ?? null, 'neighborhood' => $data['neighborhood'] ?? null, 'delivery_city' => $data['delivery_city'] ?? null, 'delivery_state' => isset($data['delivery_state']) ? strtoupper($data['delivery_state']) : null, 'total' => $total, 'cost_total' => $cost, 'deposit_amount' => $deposit, 'notes' => $data['notes'] ?? null]);
            foreach ($lines as $line) $order->items()->create(['product_id' => $line->product->id, 'description' => $line->product->name.($line->personalization ? ' · Personalização: '.$line->personalization : ''), 'type' => $line->product->type, 'quantity' => $line->quantity, 'unit_price' => $line->product->base_price, 'unit_cost' => $line->product->production_cost, 'total' => $line->total, 'total_cost' => $line->cost, 'made_to_order' => $line->product->made_to_order]);
            $payment = $order->payments()->create(['type' => 'deposit', 'status' => 'pending', 'amount' => $deposit, 'due_date' => now()->toDateString()]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'payment', 'source_id' => $payment->id, 'description' => 'Entrada do pedido '.$order->number, 'counterparty' => $customer->name, 'due_date' => now()->toDateString(), 'amount' => $deposit]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'order_balance', 'source_id' => $order->id, 'description' => 'Saldo do pedido '.$order->number, 'counterparty' => $customer->name, 'due_date' => now()->addDays(15)->toDateString(), 'amount' => $total - $deposit]);
            return $order;
        });
        session()->forget('store_cart'); return redirect()->route('loja.success', $order->number);
    }
    public function success(string $number): View { return view('store.success', ['settings' => $this->settings(), 'number' => $number, 'order' => Order::where('number', $number)->firstOrFail(), 'cartCount' => 0]); }
    public function trackingForm(): View { return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => null]); }
    public function tracking(Request $request): View
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:30'], 'email' => ['required', 'email']]);
        $order = Order::with('customer', 'items')->where('number', strtoupper(trim($data['number'])))->whereHas('customer', fn ($query) => $query->where('email', $data['email']))->first();
        if (! $order) return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => null])->withErrors(['tracking' => 'Não encontramos um pedido com estes dados. Confira o número e o e-mail informado.']);
        return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => $order]);
    }
    private function settings(): StoreSetting { return StoreSetting::firstOrCreate([], ['hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.']); }
    private function lines()
    {
        $cart = session('store_cart', []); $products = Product::whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');
        return collect($cart)->map(function ($item, $key) use ($products) { $product = $products->get($item['product_id']); if (! $product || ! $product->active || ! $product->store_visible) return null; return (object) ['key' => $key, 'product' => $product, 'quantity' => $item['quantity'], 'personalization' => $item['personalization'], 'total' => (float)$product->base_price * $item['quantity'], 'cost' => (float)$product->production_cost * $item['quantity']]; })->filter()->values();
    }
    private function cartCount(): int { return (int) $this->lines()->sum('quantity'); }
}
