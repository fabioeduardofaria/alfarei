<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResellerApplicationController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect()->route('loja.account.login');
        }

        return view('store.reseller.apply', [
            'settings' => StoreSetting::firstOrCreate([], ['hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.']),
            'cartCount' => (int) collect(session('store_cart', []))->sum('quantity'),
            'customer' => $customer,
            'application' => $customer->latestResellerApplication,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect()->route('loja.account.login');
        }
        if ($customer->customer_group === 'wholesale') {
            return back()->withErrors(['reseller' => 'Sua conta já possui condições de atacado. Contate a Alfarei para alterar o perfil.']);
        }

        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:150'],
            'sales_channel' => ['required', 'in:physical,online,social,marketplace,other'],
            'sales_url' => ['nullable', 'url', 'max:500'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        DB::transaction(function () use ($customer, $data): void {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->hasResellerPricing()) {
                throw ValidationException::withMessages(['reseller' => 'Este perfil já está aprovado para revenda.']);
            }
            $latest = $customer->resellerApplications()->latest('id')->first();
            if ($latest?->status === 'pending') {
                throw ValidationException::withMessages(['reseller' => 'Já existe uma solicitação em análise.']);
            }
            if ($latest?->status === 'needs_info') {
                $latest->update(array_merge($data, ['status' => 'pending', 'decision_note' => null, 'decided_by' => null, 'decided_at' => null]));
                $application = $latest;
                $event = 'resubmitted';
            } else {
                $application = $customer->resellerApplications()->create(array_merge($data, ['status' => 'pending']));
                $event = 'submitted';
            }
            $customer->update(['reseller_status' => 'pending']);
            $customer->resellerEvents()->create(['application_id' => $application->id, 'actor_customer_id' => $customer->id, 'event' => $event, 'note' => 'Solicitação enviada pelo cliente.']);
        });

        return redirect()->route('loja.reseller.show')->with('success', 'Solicitação enviada. A equipe Alfarei fará a análise.');
    }

    private function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer?->active ? $customer : null;
    }
}
