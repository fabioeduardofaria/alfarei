<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ResellerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResellerProgramController extends Controller
{
    public function index(): View
    {
        $applications = ResellerApplication::with('customer')->whereIn('status', ['pending', 'needs_info'])->latest()->paginate(20);
        $approved = Customer::where('customer_group', 'reseller')
            ->where(fn ($query) => $query->whereNull('reseller_status')->orWhere('reseller_status', 'approved'))
            ->withMax('orders', 'created_at')->get();
        $reviews = $approved->map(function (Customer $customer): array {
            $lastActivity = collect([$customer->reseller_approved_at, $customer->reseller_reviewed_at, $customer->orders_max_created_at, $customer->created_at])
                ->filter()->map(fn ($date) => Carbon::parse($date))->sort()->last();

            return ['customer' => $customer, 'due_at' => $lastActivity->copy()->addYear()];
        })->filter(fn ($item) => $item['due_at']->lte(now()))->sortBy('due_at')->values();
        $partners = $approved->sortBy('name')->values();

        return view('resellers.index', compact('applications', 'reviews', 'partners'));
    }

    public function show(ResellerApplication $application): View
    {
        $application->load(['customer', 'reviewer']);
        $events = $application->customer->resellerEvents()->with('staff')->latest()->limit(30)->get();

        return view('resellers.show', compact('application', 'events'));
    }

    public function customer(Customer $cliente): View
    {
        $cliente->load('latestResellerApplication');
        $events = $cliente->resellerEvents()->with('staff')->latest()->limit(30)->get();
        $lastOrder = $cliente->orders()->latest()->first();

        return view('resellers.customer', ['customer' => $cliente, 'events' => $events, 'lastOrder' => $lastOrder]);
    }

    public function decide(Request $request, ResellerApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,needs_info,reject'],
            'note' => ['required_unless:decision,approve', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($application, $data, $request): void {
            $application = ResellerApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($application->status, ['pending', 'needs_info'], true), 409, 'Esta solicitação já foi decidida.');
            $customer = Customer::whereKey($application->customer_id)->lockForUpdate()->firstOrFail();
            $status = match ($data['decision']) {
                'approve' => 'approved', 'needs_info' => 'needs_info', default => 'rejected',
            };
            $application->update(['status' => $status, 'decision_note' => $data['note'] ?? null, 'decided_by' => $request->user()->id, 'decided_at' => now()]);
            $customer->update(array_merge(
                ['reseller_status' => $status],
                $status === 'approved'
                    ? ['customer_group' => 'reseller', 'reseller_approved_at' => now(), 'reseller_reviewed_at' => now()]
                    : ['customer_group' => 'final']
            ));
            $customer->resellerEvents()->create(['application_id' => $application->id, 'actor_user_id' => $request->user()->id, 'event' => $status, 'note' => $data['note'] ?? null]);
        });

        return redirect()->route('revendedores.show', $application)->with('success', 'Decisão registrada com histórico.');
    }

    public function review(Request $request, Customer $cliente): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:review,suspend,reactivate'],
            'note' => ['required_if:action,suspend,reactivate', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($cliente, $data, $request): void {
            $customer = Customer::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $action = $data['action'];
            if ($action === 'reactivate') {
                abort_unless($customer->reseller_status === 'suspended', 409);
                $customer->update(['customer_group' => 'reseller', 'reseller_status' => 'approved', 'reseller_reviewed_at' => now()]);
            } else {
                abort_unless($customer->hasResellerPricing(), 409);
                $customer->update(['reseller_status' => $action === 'suspend' ? 'suspended' : 'approved', 'reseller_reviewed_at' => now()]);
            }
            $customer->resellerEvents()->create(['actor_user_id' => $request->user()->id, 'event' => $action === 'review' ? 'reviewed' : ($action === 'suspend' ? 'suspended' : 'reactivated'), 'note' => $data['note'] ?? null]);
        });

        return back()->with('success', 'Revisão do perfil registrada.');
    }
}
