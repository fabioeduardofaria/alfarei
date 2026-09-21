<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        return view('deliveries.index', ['orders' => Order::with('customer')->whereIn('status', ['quality', 'delivered'])->latest()->paginate(12)]);
    }
}
