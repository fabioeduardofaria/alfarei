<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StoreAccountController extends Controller
{
    public function loginForm(): View|RedirectResponse
    {
        if (Auth::guard('customer')->user()?->active) {
            return redirect()->route('loja.index');
        }
        Auth::guard('customer')->logout();

        return view('store.account.login', $this->viewData());
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::guard('customer')->attempt(array_merge($credentials, ['active' => true]))) {
            return back()->withErrors(['email' => 'E-mail ou senha inválidos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('loja.index'));
    }

    public function registerForm(): View|RedirectResponse
    {
        if (Auth::guard('customer')->user()?->active) {
            return redirect()->route('loja.index');
        }
        Auth::guard('customer')->logout();

        return view('store.account.register', $this->viewData());
    }

    public function register(Request $request): RedirectResponse
    {
        if (Auth::guard('customer')->user()?->active) {
            return redirect()->route('loja.index');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::create(array_merge($data, ['type' => 'PF', 'customer_group' => 'final', 'active' => true]));
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('loja.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerate();

        return redirect()->route('loja.index');
    }

    public function account(): View|RedirectResponse
    {
        if (! Auth::guard('customer')->user()?->active) {
            Auth::guard('customer')->logout();

            return redirect()->route('loja.account.login');
        }

        return view('store.account.profile', array_merge($this->viewData(), ['customer' => Auth::guard('customer')->user()]));
    }

    public function changePassword(Request $request): RedirectResponse
    {
        if (! Auth::guard('customer')->user()?->active) {
            Auth::guard('customer')->logout();

            return redirect()->route('loja.account.login');
        }

        $data = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        Auth::guard('customer')->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return back()->with('success', 'Senha da loja atualizada com sucesso.');
    }

    private function viewData(): array
    {
        return [
            'settings' => StoreSetting::firstOrCreate([], ['hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.']),
            'cartCount' => (int) collect(session('store_cart', []))->sum('quantity'),
        ];
    }
}
