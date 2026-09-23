<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request): void {
            if ($data['customer_group'] === 'reseller') {
                $data['reseller_status'] = 'approved';
                $data['reseller_approved_at'] = now();
                $data['reseller_reviewed_at'] = now();
            }
            $customer = Customer::create($data);
            if ($data['customer_group'] === 'reseller') {
                $customer->resellerEvents()->create(['actor_user_id' => $request->user()->id, 'event' => 'manual_approval', 'note' => 'Perfil revendedor atribuído no cadastro administrativo.']);
            }
        });

        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function edit(Customer $cliente): View
    {
        return view('customers.form', ['customer' => $cliente]);
    }

    public function update(Request $request, Customer $cliente): RedirectResponse
    {
        $data = $this->validated($request, $cliente);
        DB::transaction(function () use ($cliente, $data, $request): void {
            $previousGroup = $cliente->customer_group;
            if ($data['customer_group'] === 'reseller' && $previousGroup !== 'reseller') {
                $data['reseller_status'] = 'approved';
                $data['reseller_approved_at'] = now();
                $data['reseller_reviewed_at'] = now();
            } elseif ($previousGroup === 'reseller' && $data['customer_group'] !== 'reseller') {
                $data['reseller_status'] = 'suspended';
                $data['reseller_reviewed_at'] = now();
            }
            $cliente->update($data);
            if ($previousGroup !== $data['customer_group'] && ($previousGroup === 'reseller' || $data['customer_group'] === 'reseller')) {
                $cliente->resellerEvents()->create(['actor_user_id' => $request->user()->id, 'event' => $data['customer_group'] === 'reseller' ? 'manual_approval' : 'manual_group_change', 'note' => 'Grupo alterado manualmente de '.$previousGroup.' para '.$data['customer_group'].'.']);
            }
        });

        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $documentRule = 'unique:customers,document';
        if ($customer) {
            $documentRule .= ','.$customer->id;
        }
        $data = $request->validate([
            'type' => ['required', 'in:PF,PJ'], 'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:20', $documentRule], 'email' => ['nullable', 'required_with:password', 'email', 'max:150', 'unique:customers,email'.($customer ? ','.$customer->id : '')],
            'phone' => ['nullable', 'string', 'max:30'], 'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'], 'customer_group' => ['required', 'in:final,reseller,wholesale'],
            'notes' => ['nullable', 'string', 'max:2000'], 'active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
