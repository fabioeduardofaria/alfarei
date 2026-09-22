<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\FinancePayment;
use App\Models\Payment;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinanceEntry::query()->orderBy('due_date')->orderBy('id');
        if (in_array($request->query('tipo'), ['payable', 'receivable'], true)) {
            $query->where('type', $request->query('tipo'));
        }
        if (in_array($request->query('status'), ['pending', 'partial', 'paid', 'cancelled'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($term = trim((string) $request->query('busca'))) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$term}%")
                ->orWhere('counterparty', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%"));
        }
        if ($category = trim((string) $request->query('categoria'))) {
            $query->where('category', 'like', "%{$category}%");
        }
        if ($request->filled('de')) {
            $query->whereDate('due_date', '>=', $request->query('de'));
        }
        if ($request->filled('ate')) {
            $query->whereDate('due_date', '<=', $request->query('ate'));
        }

        $open = fn (string $type): float => (float) FinanceEntry::where('type', $type)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) AS balance')->value('balance');
        $realized = fn (string $type): float => (float) FinanceEntry::where('type', $type)
            ->whereIn('status', ['partial', 'paid'])
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' AND paid_amount = 0 THEN amount ELSE paid_amount END), 0) AS total")
            ->value('total');

        return view('finance.index', [
            'entries' => $query->paginate(16)->withQueryString(),
            'pendingReceivable' => $open('receivable'),
            'pendingPayable' => $open('payable'),
            'paidIn' => $realized('receivable'),
            'paidOut' => $realized('payable'),
            'overdueCount' => FinanceEntry::whereIn('status', ['pending', 'partial'])->whereDate('due_date', '<', today())->count(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new FinanceEntry);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedEntry($request);
        $count = (int) $data['installments'];
        $totalCents = (int) round((float) $data['amount'] * 100);
        if ($totalCents < $count) {
            throw ValidationException::withMessages(['installments' => 'O valor total deve permitir pelo menos R$ 0,01 por parcela.']);
        }
        $baseCents = intdiv($totalCents, $count);
        $remainder = $totalCents % $count;
        $group = (string) Str::uuid();
        $partner = $this->partner($data);
        $firstDue = Carbon::parse($data['due_date']);

        $first = DB::transaction(function () use ($data, $request, $count, $baseCents, $remainder, $group, $partner, $firstDue) {
            $first = null;
            for ($number = 1; $number <= $count; $number++) {
                $entry = FinanceEntry::create([
                    'type' => $data['type'], 'source_type' => 'manual', 'source_id' => null,
                    'description' => $data['description'], 'counterparty' => $partner,
                    'supplier_id' => $data['type'] === 'payable' ? ($data['supplier_id'] ?? null) : null,
                    'customer_id' => $data['type'] === 'receivable' ? ($data['customer_id'] ?? null) : null,
                    'category' => $data['category'], 'document_number' => $data['document_number'] ?? null,
                    'due_date' => $firstDue->copy()->addMonthsNoOverflow($number - 1),
                    'amount' => ($baseCents + ($number === $count ? $remainder : 0)) / 100,
                    'installment_group' => $group, 'installment_number' => $number, 'installment_count' => $count,
                    'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id,
                ]);
                $first ??= $entry;
            }

            return $first;
        });

        return redirect()->route('financeiro.show', $first)->with('success', $count > 1 ? "{$count} parcelas criadas." : 'Conta criada.');
    }

    public function show(FinanceEntry $lancamento): View
    {
        $lancamento->load(['payments.creator']);
        $siblings = $lancamento->installment_group
            ? FinanceEntry::where('installment_group', $lancamento->installment_group)->orderBy('installment_number')->get()
            : collect();

        return view('finance.show', ['entry' => $lancamento, 'siblings' => $siblings]);
    }

    public function edit(FinanceEntry $lancamento): View
    {
        abort_unless($lancamento->source_type === 'manual' && $lancamento->status === 'pending' && (float) $lancamento->paid_amount === 0.0, 409, 'Somente contas manuais sem pagamentos podem ser editadas.');

        return $this->form($lancamento);
    }

    public function update(Request $request, FinanceEntry $lancamento): RedirectResponse
    {
        $data = $this->validatedEntry($request, true);
        DB::transaction(function () use ($lancamento, $data) {
            $entry = FinanceEntry::whereKey($lancamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->source_type === 'manual' && $entry->status === 'pending' && (float) $entry->paid_amount === 0.0, 409);
            abort_unless($data['type'] === $entry->type, 422, 'A natureza de uma parcela existente não pode ser alterada.');
            $entry->update([
                'description' => $data['description'], 'counterparty' => $this->partner($data),
                'supplier_id' => $data['type'] === 'payable' ? ($data['supplier_id'] ?? null) : null,
                'customer_id' => $data['type'] === 'receivable' ? ($data['customer_id'] ?? null) : null,
                'category' => $data['category'], 'document_number' => $data['document_number'] ?? null,
                'due_date' => $data['due_date'], 'amount' => $data['amount'], 'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('financeiro.show', $lancamento)->with('success', 'Conta atualizada.');
    }

    public function settle(Request $request, FinanceEntry $lancamento): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $storedPath = null;
        try {
            DB::transaction(function () use ($request, $lancamento, $data, &$storedPath) {
                $entry = FinanceEntry::whereKey($lancamento->id)->lockForUpdate()->firstOrFail();
                if (! in_array($entry->status, ['pending', 'partial'], true)) {
                    throw ValidationException::withMessages(['amount' => 'Esta conta não possui saldo em aberto.']);
                }
                $remainingCents = (int) round($entry->remaining_amount * 100);
                $paymentCents = isset($data['amount']) ? (int) round((float) $data['amount'] * 100) : $remainingCents;
                if ($paymentCents <= 0 || $paymentCents > $remainingCents) {
                    throw ValidationException::withMessages(['amount' => 'O valor deve ser maior que zero e não pode superar o saldo em aberto.']);
                }
                if ($request->hasFile('receipt')) {
                    $storedPath = $request->file('receipt')->store('finance/receipts', 'local');
                }
                $paymentDate = $data['paid_at'] ?? today()->toDateString();
                $entry->payments()->create([
                    'created_by' => $request->user()->id, 'amount' => $paymentCents / 100,
                    'paid_at' => $paymentDate, 'payment_method' => $data['payment_method'] ?? 'manual',
                    'notes' => $data['notes'] ?? null, 'receipt_path' => $storedPath,
                    'receipt_name' => $request->file('receipt')?->getClientOriginalName(),
                ]);
                $paidCents = (int) round((float) $entry->paid_amount * 100) + $paymentCents;
                $entry->update([
                    'paid_amount' => $paidCents / 100,
                    'status' => $paidCents >= (int) round((float) $entry->amount * 100) ? 'paid' : 'partial',
                    'paid_at' => $paidCents >= (int) round((float) $entry->amount * 100) ? $paymentDate : null,
                    'payment_method' => $data['payment_method'] ?? 'manual',
                ]);
                if ($entry->status === 'paid' && $entry->source_type === 'payment') {
                    $orderPayment = Payment::with('order')->find($entry->source_id);
                    if ($orderPayment) {
                        if ($orderPayment->status !== 'paid') {
                            $orderPayment->update(['status' => 'paid', 'paid_at' => $paymentDate, 'method' => $data['payment_method'] ?? 'manual']);
                        }
                        $order = $orderPayment->order;
                        if ($orderPayment->type === 'deposit' && $order?->status === 'awaiting_deposit') {
                            $needsArt = $order->items()->where('made_to_order', true)->exists();
                            $order->update(['deposit_paid_at' => $paymentDate, 'status' => $needsArt ? 'awaiting_art' : 'ready_for_production']);
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $e;
        }

        return redirect()->route('financeiro.show', $lancamento)->with('success', 'Pagamento ou recebimento registrado.');
    }

    public function receipt(FinanceEntry $lancamento, FinancePayment $payment): StreamedResponse
    {
        abort_unless($payment->finance_entry_id === $lancamento->id && $payment->receipt_path, 404);
        abort_unless(Storage::disk('local')->exists($payment->receipt_path), 404);

        return Storage::disk('local')->download($payment->receipt_path, $payment->receipt_name);
    }

    public function cancel(FinanceEntry $lancamento): RedirectResponse
    {
        DB::transaction(function () use ($lancamento) {
            $entry = FinanceEntry::whereKey($lancamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->source_type === 'manual' && $entry->status === 'pending' && (float) $entry->paid_amount === 0.0, 409, 'Somente contas manuais sem pagamento podem ser canceladas.');
            $entry->update(['status' => 'cancelled']);
        });

        return redirect()->route('financeiro.show', $lancamento)->with('success', 'Conta cancelada; histórico preservado.');
    }

    private function form(FinanceEntry $entry): View
    {
        return view('finance.form', [
            'entry' => $entry,
            'suppliers' => Supplier::where('active', true)->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    private function validatedEntry(Request $request, bool $editing = false): array
    {
        return $request->validate([
            'type' => ['required', 'in:payable,receivable'],
            'description' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:80'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'counterparty' => ['nullable', 'string', 'max:150'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'installments' => $editing ? ['nullable'] : ['required', 'integer', 'min:1', 'max:36'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function partner(array $data): ?string
    {
        if ($data['type'] === 'payable' && ! empty($data['supplier_id'])) {
            return Supplier::findOrFail($data['supplier_id'])->name;
        }
        if ($data['type'] === 'receivable' && ! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id'])->name;
        }

        return $data['counterparty'] ?? null;
    }
}
