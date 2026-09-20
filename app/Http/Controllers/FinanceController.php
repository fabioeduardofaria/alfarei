<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinanceEntry::query()->latest('due_date');
        if ($type = $request->string('tipo')->toString()) $query->where('type', $type);
        if ($status = $request->string('status')->toString()) $query->where('status', $status);
        $pendingReceivable = (float) FinanceEntry::where('type', 'receivable')->where('status', 'pending')->sum('amount');
        $pendingPayable = (float) FinanceEntry::where('type', 'payable')->where('status', 'pending')->sum('amount');
        $paidIn = (float) FinanceEntry::where('type', 'receivable')->where('status', 'paid')->sum('amount');
        $paidOut = (float) FinanceEntry::where('type', 'payable')->where('status', 'paid')->sum('amount');
        return view('finance.index', compact('query', 'pendingReceivable', 'pendingPayable', 'paidIn', 'paidOut') + ['entries' => $query->paginate(16)->withQueryString()]);
    }

    public function settle(Request $request, FinanceEntry $lancamento): RedirectResponse
    {
        if ($lancamento->status === 'paid') return back()->with('success', 'Este lançamento já está baixado.');
        $data = $request->validate(['payment_method' => ['nullable', 'string', 'max:40']]);
        $lancamento->update(['status' => 'paid', 'paid_at' => now(), 'payment_method' => $data['payment_method'] ?? 'manual']);
        return back()->with('success', $lancamento->type === 'receivable' ? 'Recebimento registrado.' : 'Pagamento registrado.');
    }
}
