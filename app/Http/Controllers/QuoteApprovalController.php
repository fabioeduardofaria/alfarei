<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuoteApprovalController extends Controller
{
    public function show(string $token): View
    {
        $quote = $this->quote($token);
        $this->expireIfNeeded($quote);

        return view('quotes.public', compact('quote'));
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $quote = $this->quote($token);
        $this->expireIfNeeded($quote);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'response' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = Quote::whereKey($quote->id)->whereIn('status', ['sent', 'negotiation'])->update([
            'status' => $data['decision'],
            'customer_response' => $data['response'] ?? null,
            'approved_at' => $data['decision'] === 'approved' ? now() : null,
            'rejected_at' => $data['decision'] === 'rejected' ? now() : null,
        ]);
        if (! $updated) {
            return back()->withErrors(['response' => 'Esta versão já foi respondida ou substituída. Confira a situação atual da proposta.']);
        }

        return redirect()->route('proposta.public', $quote->approval_token)
            ->with('success', $data['decision'] === 'approved'
                ? 'Proposta aprovada com sucesso. A Alfarei dará continuidade ao seu atendimento.'
                : 'Sua resposta foi registrada. Obrigado pelo seu retorno.');
    }

    public function pdf(string $token): Response
    {
        $quote = $this->quote($token);

        return Pdf::loadView('quotes.pdf', compact('quote'))
            ->setPaper('a4')
            ->download('proposta-'.str($quote->number)->lower()->replace(' ', '-').'.pdf');
    }

    private function quote(string $token): Quote
    {
        return Quote::with(['customer', 'items.product'])
            ->where('approval_token', $token)
            ->firstOrFail();
    }

    private function expireIfNeeded(Quote $quote): void
    {
        if ($quote->valid_until?->isBefore(today()) && in_array($quote->status, ['sent', 'negotiation'], true)) {
            $quote->update(['status' => 'expired']);
            $quote->refresh();
        }
    }
}
