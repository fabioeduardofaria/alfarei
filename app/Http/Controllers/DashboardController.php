<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'customers' => Customer::count(),
            'materials' => Material::count(),
            'criticalMaterials' => Material::whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'products' => Product::count(),
            'recentCustomers' => Customer::latest()->take(5)->get(),
        ]);
    }
}
