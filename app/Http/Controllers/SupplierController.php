<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('document', 'like', "%{$search}%"));
        return view('suppliers.index', ['suppliers' => $query->paginate(12)->withQueryString()]);
    }
    public function create(): View { return view('suppliers.form', ['supplier' => new Supplier()]); }
    public function store(Request $request): RedirectResponse { Supplier::create($this->validated($request)); return redirect()->route('fornecedores.index')->with('success', 'Fornecedor cadastrado com sucesso.'); }
    public function edit(Supplier $fornecedore): View { return view('suppliers.form', ['supplier' => $fornecedore]); }
    public function update(Request $request, Supplier $fornecedore): RedirectResponse { $fornecedore->update($this->validated($request, $fornecedore)); return redirect()->route('fornecedores.index')->with('success', 'Fornecedor atualizado com sucesso.'); }
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:150'], 'document' => ['nullable', 'string', 'max:24', 'unique:suppliers,document'.($supplier ? ','.$supplier->id : '')], 'email' => ['nullable', 'email', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'contact_name' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:2000'], 'active' => ['boolean']]);
    }
}
