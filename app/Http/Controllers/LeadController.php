<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadActivityRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(private readonly LeadWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        $leads = Lead::with(['customer', 'responsible'])
            ->when($request->filled('stage'), fn ($query) => $query->where('stage', $request->string('stage')))
            ->when($request->filled('busca'), fn ($query) => $query->where(fn ($search) => $search->where('name', 'like', '%'.$request->string('busca').'%')->orWhere('company', 'like', '%'.$request->string('busca').'%')->orWhere('email', 'like', '%'.$request->string('busca').'%')))
            ->latest()->paginate(24)->withQueryString();

        return view('crm.index', ['leads' => $leads, 'stages' => Lead::STAGES]);
    }

    public function create(): View
    {
        return $this->form(new Lead(['stage' => 'new', 'probability' => 10]));
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = $this->workflow->create($request->validated(), $request->user());

        return redirect()->route('crm.show', $lead)->with('success', 'Oportunidade criada no CRM.');
    }

    public function show(Lead $lead): View
    {
        return view('crm.show', ['lead' => $lead->load(['customer', 'responsible', 'activities.user']), 'stages' => Lead::STAGES]);
    }

    public function edit(Lead $lead): View
    {
        return $this->form($lead);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->workflow->update($lead, $request->validated(), $request->user());

        return redirect()->route('crm.show', $lead)->with('success', 'Oportunidade atualizada.');
    }

    public function addActivity(StoreLeadActivityRequest $request, Lead $lead): RedirectResponse
    {
        $this->workflow->addActivity($lead, $request->validated(), $request->user());

        return back()->with('success', 'Interação registrada no histórico.');
    }

    public function changeStage(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['stage' => ['required', Rule::in(array_keys(Lead::STAGES))]]);
        $this->workflow->update($lead, array_merge($lead->only($lead->getFillable()), $data), $request->user());

        return back()->with('success', 'Etapa atualizada para '.Lead::STAGES[$data['stage']].'.');
    }

    public function convertToCustomer(Request $request, Lead $lead): RedirectResponse
    {
        return redirect()->route('clientes.edit', $this->workflow->convertToCustomer($lead, $request->user()))->with('success', 'Cliente vinculado à oportunidade.');
    }

    private function form(Lead $lead): View
    {
        return view('crm.form', ['lead' => $lead, 'stages' => Lead::STAGES, 'customers' => Customer::where('active', true)->orderBy('name')->get(), 'users' => User::where('active', true)->orderBy('name')->get()]);
    }
}
