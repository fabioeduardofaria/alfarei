<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proposta {{ $quote->number }} · Alfarei</title>
    <style>
        :root { --ink:#173127; --gold:#bd8a15; --paper:#fff; --mist:#f5f4ef; --line:#d9dfda; --muted:#62736a; }
        * { box-sizing:border-box; } body { margin:0; background:var(--mist); color:#14251d; font:15px Arial, sans-serif; line-height:1.5; }
        .top { background:var(--ink); color:#fff; padding:24px 28px; } .top-inner,.page { max-width:980px; margin:auto; }
        .brand { display:flex; gap:13px; align-items:center; font-weight:700; letter-spacing:.08em; } .mark { width:38px; height:38px; border:1px solid var(--gold); border-radius:50%; display:grid; place-items:center; color:#e9c25a; font:700 23px Georgia,serif; }
        .brand small { display:block; color:#ddbe67; letter-spacing:.16em; font-size:9px; margin-top:1px; } .top h1 { font:700 30px Georgia,serif; margin:22px 0 3px; } .top p { margin:0; color:#d4ddd7; }
        .page { padding:26px 20px 42px; } .card { background:var(--paper); border:1px solid var(--line); border-radius:10px; padding:26px; margin-bottom:18px; box-shadow:0 8px 28px rgba(25,47,36,.06); }
        .summary { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; } .label { color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.08em; } .value { font-weight:700; margin-top:3px; }
        h2 { font:700 21px Georgia,serif; margin:0 0 16px; } table { width:100%; border-collapse:collapse; } th { text-align:left; padding:10px 8px; color:var(--muted); font-size:11px; letter-spacing:.08em; border-bottom:1px solid var(--line); } td { padding:13px 8px; border-bottom:1px solid #edf0ed; } th:last-child, td:last-child { text-align:right; } .total { display:flex; justify-content:flex-end; gap:36px; padding-top:18px; font-size:18px; } .total b { font-size:23px; color:var(--ink); }
        .details { display:grid; grid-template-columns:1fr 1fr; gap:18px; } .notice { border-left:4px solid var(--gold); padding:12px 15px; background:#fffaf0; } .success { background:#edf7f0; border-left-color:#23824a; } .danger { background:#fff2f0; border-left-color:#bf4b40; }
        textarea { width:100%; min-height:88px; border:1px solid var(--line); border-radius:7px; padding:10px; font:inherit; } .actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:15px; } button,.button { border:0; border-radius:7px; padding:12px 17px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; } .approve { background:var(--ink); color:#fff; } .reject { background:#fff; color:#9f332b; border:1px solid #d8aaa4; } .download { color:var(--ink); background:#fff; border:1px solid var(--line); }
        .flash { padding:13px 16px; color:#17643a; background:#eaf7ee; border:1px solid #b9ddc4; border-radius:8px; margin-bottom:18px; } .errors { color:#9f332b; margin:0 0 10px; } footer { text-align:center; color:var(--muted); font-size:12px; padding:8px; }
        @media (max-width:650px) { .summary,.details { grid-template-columns:1fr; } .card { padding:19px; } .top h1 { font-size:26px; } }
    </style>
</head>
<body>
@php($status = ['draft'=>'Rascunho','sent'=>'Aguardando sua decisão','negotiation'=>'Em negociação','approved'=>'Aprovada','rejected'=>'Recusada','expired'=>'Expirada','cancelled'=>'Cancelada','superseded'=>'Substituída por nova versão'][$quote->status] ?? $quote->status)
<header class="top"><div class="top-inner"><div class="brand"><span class="mark">A</span><span>ALFAREI<small>CNC · OPERAÇÃO</small></span></div><h1>Proposta comercial</h1><p>{{ $quote->number }} · Versão {{ $quote->version }}</p></div></header>
<main class="page">
    @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
    <section class="card summary"><div><div class="label">Cliente</div><div class="value">{{ $quote->customer->name }}</div></div><div><div class="label">Validade</div><div class="value">{{ $quote->valid_until?->format('d/m/Y') ?? 'Não informada' }}</div></div><div><div class="label">Situação</div><div class="value">{{ $status }}</div></div></section>
    <section class="card"><h2>Itens da proposta</h2><table><thead><tr><th>Descrição</th><th>Qtd.</th><th>Valor unitário</th><th>Total</th></tr></thead><tbody>@foreach($quote->items as $item)<tr><td><strong>{{ $item->description }}</strong><br><small>{{ $item->type === 'product' ? 'Produto' : 'Serviço personalizado' }}</small></td><td>{{ number_format($item->quantity, 3, ',', '.') }}</td><td>R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td><td>R$ {{ number_format($item->total, 2, ',', '.') }}</td></tr>@endforeach</tbody></table><div class="total"><span>Valor total</span><b>R$ {{ number_format($quote->total, 2, ',', '.') }}</b></div></section>
    <section class="card details"><div><h2>Condições</h2><p><strong>Entrada:</strong> {{ number_format($quote->deposit_percent, 0, ',', '.') }}%<br><strong>Prazo de produção:</strong> {{ $quote->production_lead_days ? $quote->production_lead_days.' dias úteis' : 'A combinar' }}<br><strong>Entrega/despacho:</strong> {{ $quote->delivery_lead_days ? $quote->delivery_lead_days.' dias úteis após a produção' : 'A combinar' }}</p><p>{!! nl2br(e($quote->payment_terms ?: 'Condições de pagamento a combinar com a equipe comercial.')) !!}</p></div><div><h2>Observações</h2><p>{!! nl2br(e($quote->notes ?: 'Proposta preparada especialmente para você.')) !!}</p><a class="button download" href="{{ route('proposta.pdf', $quote->approval_token) }}">Baixar PDF profissional</a></div></section>
    @if(in_array($quote->status, ['sent', 'negotiation']))
        <section class="card"><h2>Decisão sobre a proposta</h2><div class="notice">Ao aprovar, você confirma esta versão da proposta. A equipe Alfarei será avisada para dar continuidade.</div>@if($errors->any())<p class="errors">{{ $errors->first() }}</p>@endif<form method="POST" action="{{ route('proposta.respond', $quote->approval_token) }}">@csrf<label for="response">Mensagem para a equipe (opcional)</label><textarea id="response" name="response" placeholder="Ex.: pode seguir com a produção.">{{ old('response') }}</textarea><div class="actions"><button class="approve" name="decision" value="approved">Aprovar proposta</button><button class="reject" name="decision" value="rejected">Recusar proposta</button></div></form></section>
    @elseif($quote->status === 'approved')
        <section class="card"><div class="notice success"><strong>Proposta aprovada em {{ $quote->approved_at?->format('d/m/Y \à\s H:i') }}.</strong><br>A equipe Alfarei seguirá com o atendimento.</div>@if($quote->customer_response)<p><strong>Sua mensagem:</strong> {{ $quote->customer_response }}</p>@endif</section>
    @elseif($quote->status === 'rejected')
        <section class="card"><div class="notice danger"><strong>Resposta registrada em {{ $quote->rejected_at?->format('d/m/Y \à\s H:i') }}.</strong><br>Se desejar rever a proposta, entre em contato com a Alfarei.</div></section>
    @else
        <section class="card"><div class="notice danger">Esta proposta não está disponível para aprovação. Entre em contato com a Alfarei caso precise de uma nova versão.</div></section>
    @endif
    <footer>Alfarei CNC · Proposta {{ $quote->number }} · Este link é individual e seguro.</footer>
</main>
</body>
</html>
