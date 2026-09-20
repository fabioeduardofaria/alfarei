<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('document', 'like', "%{$search}%"));
        }
        return view('customers.index', ['customers' => $query->paginate(12)->withQueryString()]);
    }

    public function create(): View { return view('customers.form', ['customer' => new Customer()]); }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validated($request));
        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function edit(Customer $cliente): View { return view('customers.form', ['customer' => $cliente]); }

    public function update(Request $request, Customer $cliente): RedirectResponse
    {
        $cliente->update($this->validated($request, $cliente));
        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $documentRule = 'unique:customers,document';
        if ($customer) $documentRule .= ','.$customer->id;
        return $request->validate([
            'type' => ['required', 'in:PF,PJ'], 'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:20', $documentRule], 'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'], 'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'], 'customer_group' => ['required', 'in:final,reseller,wholesale'],
            'notes' => ['nullable', 'string', 'max:2000'], 'active' => ['boolean'],
        ]);
    }
}
