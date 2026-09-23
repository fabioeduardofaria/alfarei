<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CustomerNotificationService;
use App\Services\StorePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(private readonly CustomerNotificationService $notifications, private readonly StorePricingService $pricing) {}

    public function index(Request $request): View
    {
        $intent = $request->query('uso');
        $intent = is_string($intent) && in_array($intent, ['presente', 'decoracao', 'empresa', 'personalizavel', 'pronta-entrega'], true) ? $intent : null;
        $search = $request->query('busca');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';
        $sort = $request->query('ordenar');
        $sort = is_string($sort) && in_array($sort, ['destaques', 'recentes', 'menor-preco', 'maior-preco'], true) ? $sort : 'destaques';

        $available = Product::query()->where('active', true)->where('store_visible', true);
        $products = clone $available;
        if (in_array($intent, ['presente', 'decoracao', 'empresa'], true)) {
            $products->whereJsonContains('store_occasions', $intent);
        } elseif ($intent === 'personalizavel') {
            $products->where('allow_personalization', true);
        } elseif ($intent === 'pronta-entrega') {
            $products->where('made_to_order', false);
        }
        if ($search !== '') {
            $products->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        $priceColumn = match ($this->pricing->effectiveGroup($this->customer())) {
            'reseller' => 'COALESCE(reseller_price, base_price)',
            'wholesale' => 'COALESCE(wholesale_price, base_price)',
            default => 'base_price',
        };
        match ($sort) {
            'recentes' => $products->latest(),
            'menor-preco' => $products->orderByRaw($priceColumn.' ASC')->orderBy('name'),
            'maior-preco' => $products->orderByRaw($priceColumn.' DESC')->orderBy('name'),
            default => $products->orderByDesc('store_featured')->orderBy('name'),
        };

        return view('store.index', [
            'settings' => $this->settings(),
            'products' => $products->paginate(12)->withQueryString(),
            'featured' => (clone $available)->where('store_featured', true)->orderBy('name')->limit(3)->get(),
            'intent' => $intent, 'search' => $search, 'sort' => $sort,
            'cartCount' => $this->cartCount(), 'pricing' => $this->pricing, 'storeCustomer' => $this->customer(),
        ]);
    }

    public function product(Product $produto): View
    {
        abort_unless($produto->active && $produto->store_visible, 404);
        $produto->load('images');
        $images = collect([$produto->image_url])
            ->filter()
            ->merge($produto->images->pluck('path'))
            ->unique()
            ->values();

        $related = Product::query()->where('active', true)->where('store_visible', true)->whereKeyNot($produto->id);
        if ($produto->store_occasions) {
            $occasions = $produto->store_occasions;
            $related->where(function ($query) use ($occasions): void {
                foreach ($occasions as $occasion) {
                    $query->orWhereJsonContains('store_occasions', $occasion);
                }
            });
        }
        $suggestions = $related->orderByDesc('store_featured')->orderBy('name')->limit(3)->get();
        if ($suggestions->count() < 3) {
            $remaining = Product::query()->where('active', true)->where('store_visible', true)
                ->whereNotIn('id', $suggestions->pluck('id')->push($produto->id))
                ->orderByDesc('store_featured')->orderBy('name')->limit(3 - $suggestions->count())->get();
            $suggestions = $suggestions->concat($remaining);
        }

        return view('store.product', ['settings' => $this->settings(), 'product' => $produto, 'images' => $images, 'related' => $suggestions, 'cartCount' => $this->cartCount(), 'pricing' => $this->pricing, 'storeCustomer' => $this->customer()]);
    }

    public function cart(): View
    {
        return view('store.cart', ['settings' => $this->settings(), 'lines' => $this->lines(), 'cartCount' => $this->cartCount(), 'storeCustomer' => $this->customer()]);
    }

    public function add(Request $request, Product $produto): RedirectResponse
    {
        abort_unless($produto->active && $produto->store_visible, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100'], 'personalization' => ['nullable', 'string', 'max:500']]);
        $cart = session('store_cart', []);
        $key = $produto->id.'|'.($data['personalization'] ?? '');
        if (($cart[$key]['quantity'] ?? 0) + $data['quantity'] > 100) {
            return back()->withErrors(['quantity' => 'O limite é de 100 unidades por item.']);
        }
        $cart[$key] = ['product_id' => $produto->id, 'quantity' => ($cart[$key]['quantity'] ?? 0) + $data['quantity'], 'personalization' => $data['personalization'] ?? null];
        session(['store_cart' => $cart]);

        return redirect()->route('loja.cart')->with('success', 'Item adicionado ao carrinho.');
    }

    public function remove(Request $request, string $key): RedirectResponse
    {
        $cart = session('store_cart', []);
        unset($cart[$key]);
        session(['store_cart' => $cart]);

        return back()->with('success', 'Item removido do carrinho.');
    }

    public function checkout(): View
    {
        $lines = $this->lines();
        abort_if($lines->isEmpty(), 404);

        return view('store.checkout', ['settings' => $this->settings(), 'lines' => $lines, 'cartCount' => $this->cartCount(), 'storeCustomer' => $this->customer()]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $settings = $this->settings();
        if (! $settings->store_open) {
            return back()->withErrors(['store' => 'A loja está temporariamente indisponível.']);
        }
        $lines = $this->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('loja.index');
        }
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150'], 'phone' => ['required', 'string', 'max:30'], 'city' => ['required', 'string', 'max:100'], 'state' => ['required', 'string', 'size:2'], 'delivery_method' => ['required', 'in:pickup,shipping'], 'postal_code' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:12'], 'street' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:150'], 'street_number' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:20'], 'complement' => ['nullable', 'string', 'max:100'], 'neighborhood' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:100'], 'delivery_city' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:100'], 'delivery_state' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'size:2'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $authenticatedCustomer = $this->customer();
        if ($authenticatedCustomer) {
            $data['name'] = $authenticatedCustomer->name;
            $data['email'] = $authenticatedCustomer->email;
        } else {
            $existingCustomer = Customer::where('email', $data['email'])->first();
            if ($existingCustomer && ($existingCustomer->password || $existingCustomer->customer_group !== 'final')) {
                return back()->withErrors(['email' => 'Esta conta já existe. Entre na loja para concluir o pedido.'])->onlyInput('name', 'email', 'phone', 'city', 'state');
            }
        }
        if (($data['delivery_method'] === 'pickup' && ! $settings->pickup_enabled) || ($data['delivery_method'] === 'shipping' && ! $settings->shipping_enabled)) {
            return back()->withErrors(['delivery_method' => 'Esta modalidade de recebimento não está disponível no momento.']);
        }

        $order = DB::transaction(function () use ($data, $lines, $settings, $authenticatedCustomer) {
            $customer = $authenticatedCustomer ?? Customer::firstOrCreate(['email' => $data['email']], ['type' => 'PF', 'name' => $data['name'], 'phone' => $data['phone'], 'city' => $data['city'], 'state' => strtoupper($data['state']), 'customer_group' => 'final', 'active' => true]);
            $total = $lines->sum('total');
            $cost = $lines->sum('cost');
            $deposit = round($total * ((float) $settings->deposit_percent / 100), 2);
            $order = Order::create(['number' => sprintf('PED-%s-%04d', now()->format('Y'), Order::count() + 1), 'customer_id' => $customer->id, 'created_by' => User::where('active', true)->value('id') ?? 1, 'status' => 'awaiting_deposit', 'source' => 'ecommerce', 'delivery_method' => $data['delivery_method'], 'postal_code' => $data['postal_code'] ?? null, 'street' => $data['street'] ?? null, 'street_number' => $data['street_number'] ?? null, 'complement' => $data['complement'] ?? null, 'neighborhood' => $data['neighborhood'] ?? null, 'delivery_city' => $data['delivery_city'] ?? null, 'delivery_state' => isset($data['delivery_state']) ? strtoupper($data['delivery_state']) : null, 'total' => $total, 'cost_total' => $cost, 'deposit_amount' => $deposit, 'notes' => $data['notes'] ?? null]);
            foreach ($lines as $line) {
                $order->items()->create(['product_id' => $line->product->id, 'description' => $line->product->name.($line->personalization ? ' · Personalização: '.$line->personalization : ''), 'type' => $line->product->type, 'quantity' => $line->quantity, 'unit_price' => $line->unitPrice, 'unit_cost' => $line->product->production_cost, 'total' => $line->total, 'total_cost' => $line->cost, 'made_to_order' => $line->product->made_to_order]);
            }
            $payment = $order->payments()->create(['type' => 'deposit', 'status' => 'pending', 'amount' => $deposit, 'due_date' => now()->toDateString()]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'payment', 'source_id' => $payment->id, 'description' => 'Entrada do pedido '.$order->number, 'counterparty' => $customer->name, 'due_date' => now()->toDateString(), 'amount' => $deposit]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'order_balance', 'source_id' => $order->id, 'description' => 'Saldo do pedido '.$order->number, 'counterparty' => $customer->name, 'due_date' => now()->addDays(15)->toDateString(), 'amount' => $total - $deposit]);

            return $order;
        });
        $this->notifications->queue($order, 'order_created');
        session()->forget('store_cart');

        return redirect()->route('loja.success', $order->number);
    }

    public function success(string $number): View
    {
        return view('store.success', ['settings' => $this->settings(), 'number' => $number, 'order' => Order::where('number', $number)->firstOrFail(), 'cartCount' => 0]);
    }

    public function trackingForm(): View
    {
        return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => null]);
    }

    public function tracking(Request $request): View
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:30'], 'email' => ['required', 'email']]);
        $order = Order::with('customer', 'items')->where('number', strtoupper(trim($data['number'])))->whereHas('customer', fn ($query) => $query->where('email', $data['email']))->first();
        if (! $order) {
            return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => null])->withErrors(['tracking' => 'Não encontramos um pedido com estes dados. Confira o número e o e-mail informado.']);
        }

        return view('store.tracking', ['settings' => $this->settings(), 'cartCount' => $this->cartCount(), 'order' => $order]);
    }

    private function settings(): StoreSetting
    {
        return StoreSetting::firstOrCreate([], ['hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.']);
    }

    private function lines()
    {
        $cart = session('store_cart', []);
        $products = Product::whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');
        $quantities = collect($cart)->groupBy('product_id')->map(fn ($items) => $items->sum('quantity'));

        return collect($cart)->map(function ($item, $key) use ($products, $quantities) {
            $product = $products->get($item['product_id']);
            if (! $product || ! $product->active || ! $product->store_visible) {
                return null;
            }

            $quantity = (int) $item['quantity'];
            $unitPrice = $this->pricing->unitPrice($product, $this->customer(), (int) $quantities->get($product->id));

            return (object) ['key' => $key, 'product' => $product, 'quantity' => $quantity, 'personalization' => $item['personalization'], 'unitPrice' => $unitPrice, 'total' => $unitPrice * $quantity, 'cost' => (float) $product->production_cost * $quantity];
        })->filter()->values();
    }

    private function cartCount(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    private function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer?->active ? $customer : null;
    }
}
