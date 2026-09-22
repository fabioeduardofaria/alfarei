<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteAttachment;
use App\Services\QuotePricingService;
use App\Services\QuoteToOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(private readonly QuotePricingService $pricing, private readonly QuoteToOrderService $orderService) {}

    public function index(Request $request): View
    {
        Quote::whereIn('status', ['sent', 'negotiation'])
            ->whereDate('valid_until', '<', today())
            ->update(['status' => 'expired']);
        $quotes = Quote::with('customer')->latest();
        if ($status = $request->string('status')->trim()->toString()) {
            $quotes->where('status', $status);
        }

        return view('quotes.index', ['quotes' => $quotes->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->form(new Quote(['status' => 'draft', 'valid_until' => now()->addDays(7)]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = DB::transaction(function () use ($data, $request) {
            $calculated = $this->calculate($data);
            $number = sprintf('ORC-%s-%04d', now()->format('Y'), Quote::count() + 1);
            $quote = Quote::create(array_merge(collect($data)->except('items')->all(), $calculated, ['number' => $number, 'version' => 1, 'created_by' => $request->user()->id, 'status' => 'draft']));
            $quote->items()->createMany($calculated['items']);

            return $quote;
        });

        return redirect()->route('orcamentos.edit', $quote)->with('success', 'Orçamento '.$quote->number.' criado com sucesso.');
    }

    public function edit(Quote $orcamento): View
    {
        if ($orcamento->status !== 'draft') {
            $orcamento->load(['customer', 'creator', 'items', 'attachments.uploader', 'parent']);
            $root = $orcamento->parent ?? $orcamento;

            return view('quotes.review', [
                'quote' => $orcamento,
                'versions' => Quote::where('id', $root->id)->orWhere('parent_quote_id', $root->id)->orderBy('version')->get(),
            ]);
        }

        return $this->form($orcamento->load('items'));
    }

    public function update(Request $request, Quote $orcamento): RedirectResponse
    {
        abort_unless($orcamento->status === 'draft', 409, 'Esta versão já foi publicada. Crie uma nova versão para alterar a proposta.');
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $orcamento) {
            $orcamento = Quote::whereKey($orcamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($orcamento->status === 'draft', 409, 'Esta versão já foi publicada. Crie uma nova versão para alterar a proposta.');
            $calculated = $this->calculate($data);
            $orcamento->update(array_merge(collect($data)->except('items')->all(), $calculated, ['status' => 'draft']));
            $orcamento->items()->delete();
            $orcamento->items()->createMany($calculated['items']);
        });

        return redirect()->route('orcamentos.edit', $orcamento)->with('success', 'Rascunho salvo. Revise os valores e clique em Enviar proposta quando estiver pronto.');
    }

    public function send(Quote $orcamento): RedirectResponse
    {
        DB::transaction(function () use ($orcamento) {
            $quote = Quote::whereKey($orcamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($quote->status === 'draft', 409, 'Somente rascunhos podem ser publicados.');
            if (! $quote->valid_until || $quote->valid_until->isBefore(today()) || ! $quote->items()->exists() || $quote->total <= 0) {
                throw ValidationException::withMessages([
                    'send' => 'Informe uma validade futura e pelo menos um item com valor antes de enviar.',
                ]);
            }

            $quote->update([
                'status' => 'sent',
                'approval_token' => $quote->approval_token ?: Str::random(60),
                'sent_at' => now(),
            ]);

            if ($quote->parent_quote_id) {
                Quote::where(function ($query) use ($quote) {
                    $query->whereKey($quote->parent_quote_id)->orWhere('parent_quote_id', $quote->parent_quote_id);
                })->where('id', '!=', $quote->id)->whereIn('status', ['sent', 'negotiation'])->update(['status' => 'superseded']);
            }
        });

        return redirect()->route('orcamentos.edit', $orcamento)->with('success', 'Versão publicada e bloqueada. O link está pronto para ser compartilhado com o cliente.');
    }

    public function convertToOrder(Quote $orcamento, Request $request): RedirectResponse
    {
        if ($orcamento->status !== 'approved') {
            return back()->withErrors(['status' => 'Aprove o orçamento antes de convertê-lo em pedido.']);
        }
        $order = $this->orderService->convert($orcamento, $request->user()->id);

        return redirect()->route('pedidos.show', $order)->with('success', 'Pedido '.$order->number.' criado. Confirme a entrada para seguir.');
    }

    public function createRevision(Quote $orcamento, Request $request): RedirectResponse
    {
        abort_if($orcamento->status === 'draft', 409, 'Conclua o rascunho atual antes de criar outra versão.');
        $orcamento->load(['items', 'attachments']);
        $rootNumber = preg_replace('/-V\d+$/', '', $orcamento->parent?->number ?? $orcamento->number);
        $nextVersion = Quote::where('number', 'like', $rootNumber.'%')->max('version') + 1;
        $revision = DB::transaction(function () use ($orcamento, $request, $rootNumber, $nextVersion) {
            $revision = Quote::create($orcamento->only(['customer_id', 'valid_until', 'notes', 'payment_terms', 'production_lead_days', 'delivery_lead_days', 'deposit_percent', 'discount', 'tax_percent', 'commission_percent', 'fee_percent', 'target_margin_percent', 'discount_percent', 'suggested_total', 'subtotal', 'cost_total', 'total']) + [
                'number' => $rootNumber.'-V'.$nextVersion, 'parent_quote_id' => $orcamento->parent_quote_id ?: $orcamento->id,
                'created_by' => $request->user()->id, 'version' => $nextVersion, 'status' => 'draft',
            ]);
            $revision->items()->createMany($orcamento->items->map(fn ($item) => $item->only(['product_id', 'description', 'type', 'quantity', 'unit_price', 'unit_cost', 'total', 'total_cost']))->all());
            foreach ($orcamento->attachments->where('approved', true) as $attachment) {
                if (! Storage::disk('public')->exists($attachment->path)) {
                    continue;
                }
                $extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
                $path = "quotes/{$revision->id}/".Str::random(40).($extension ? '.'.$extension : '');
                Storage::disk('public')->copy($attachment->path, $path);
                $revision->attachments()->create($attachment->only(['original_name', 'mime_type', 'size', 'category']) + [
                    'uploaded_by' => $request->user()->id, 'path' => $path, 'version' => 1, 'approved' => false,
                ]);
            }

            return $revision;
        });

        return redirect()->route('orcamentos.edit', $revision)->with('success', 'Nova versão criada sem alterar o histórico anterior.');
    }

    private function form(Quote $quote): View
    {
        return view('quotes.form', ['quote' => $quote, 'customers' => Customer::where('active', true)->orderBy('name')->get(), 'products' => Product::where('active', true)->orderBy('name')->get()]);
    }

    public function storeAttachment(Request $request, Quote $orcamento): RedirectResponse
    {
        abort_unless($orcamento->status === 'draft', 409, 'Arquivos de uma proposta publicada não podem ser alterados.');
        $data = $request->validate(['file' => ['required', 'file', 'max:51200', 'mimes:dxf,svg,cdr,ai,pdf,jpg,jpeg,png,nc,tap,gcode'], 'category' => ['required', 'in:technical,art,reference,gcode']]);
        $file = $data['file'];
        $version = ((int) $orcamento->attachments()->where('category', $data['category'])->max('version')) + 1;
        $path = $file->store("quotes/{$orcamento->id}", 'public');
        $orcamento->attachments()->create(['uploaded_by' => $request->user()->id, 'original_name' => $file->getClientOriginalName(), 'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'category' => $data['category'], 'version' => $version]);

        return back()->with('success', 'Arquivo técnico anexado como versão '.$version.'.');
    }

    public function approveAttachment(Quote $orcamento, QuoteAttachment $attachment): RedirectResponse
    {
        abort_unless($orcamento->status === 'draft', 409, 'Arquivos de uma proposta publicada não podem ser alterados.');
        abort_unless($attachment->quote_id === $orcamento->id, 404);
        $orcamento->attachments()->where('category', $attachment->category)->update(['approved' => false]);
        $attachment->update(['approved' => true]);

        return back()->with('success', 'Arquivo marcado como aprovado para esta versão do orçamento.');
    }

    public function destroyAttachment(Quote $orcamento, QuoteAttachment $attachment): RedirectResponse
    {
        abort_unless($orcamento->status === 'draft', 409, 'Arquivos de uma proposta publicada não podem ser alterados.');
        abort_unless($attachment->quote_id === $orcamento->id, 404);
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Arquivo removido.');
    }

    private function validated(Request $request): array
    {
        $request->mergeIfMissing(['deposit_percent' => 50]);

        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'], 'status' => ['nullable', 'in:draft'],
            'valid_until' => ['nullable', 'date'], 'discount' => ['required', 'numeric', 'min:0'], 'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'target_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:99.99'], 'notes' => ['nullable', 'string', 'max:3000'], 'payment_terms' => ['nullable', 'string', 'max:3000'], 'production_lead_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'delivery_lead_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'deposit_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:200'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function calculate(array $data): array
    {
        return $this->pricing->calculate($data['items'], (float) $data['discount'], (float) ($data['discount_percent'] ?? 0), (float) ($data['tax_percent'] ?? 0), (float) ($data['commission_percent'] ?? 0), (float) ($data['fee_percent'] ?? 0), (float) ($data['target_margin_percent'] ?? 20));
    }
}
