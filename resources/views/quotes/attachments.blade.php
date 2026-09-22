@php($readonly = $readonly ?? false)
<section class="panel attachments-panel">
    <div>
        <p class="eyebrow">ARQUIVOS TÉCNICOS · V{{ $quote->version }}</p>
        <h2>Arte, desenho e arquivos de produção</h2>
        <p class="muted">{{ $readonly ? 'Arquivos preservados nesta versão publicada.' : 'Marque o arquivo correto como aprovado antes de publicar a proposta.' }}</p>
    </div>
    @unless($readonly)
        <form method="POST" action="{{ route('orcamentos.attachments.store', $quote) }}" enctype="multipart/form-data" class="attachment-upload">
            @csrf
            <label>Tipo
                <select name="category">
                    <option value="technical">Desenho técnico</option>
                    <option value="art">Arte</option>
                    <option value="reference">Referência</option>
                    <option value="gcode">G-code</option>
                </select>
            </label>
            <label class="file-field">Arquivo
                <input type="file" name="file" accept=".dxf,.svg,.cdr,.ai,.pdf,.jpg,.jpeg,.png,.nc,.tap,.gcode" required>
                <small>DXF, SVG, CDR, AI, PDF, JPG, PNG ou G-code · até 50 MB.</small>
            </label>
            <button class="primary">Anexar arquivo</button>
        </form>
    @endunless
    <div class="attachment-list">
        @forelse($quote->attachments()->with('uploader')->latest()->get() as $attachment)
            <div class="attachment-row">
                <div>
                    <a class="edit" target="_blank" href="{{ asset('storage/'.$attachment->path) }}"><b>{{ $attachment->original_name }}</b></a>
                    <small>{{ strtoupper($attachment->category) }} · Arquivo V{{ $attachment->version }} · {{ number_format($attachment->size / 1024, 1, ',', '.') }} KB</small>
                    <small>Enviado por {{ $attachment->uploader?->name ?? 'Usuário removido' }} em {{ $attachment->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="attachment-actions">
                    @if($attachment->approved)
                        <span class="approved-tag">Aprovado nesta versão</span>
                    @elseif(!$readonly)
                        <form method="POST" action="{{ route('orcamentos.attachments.approve', [$quote, $attachment]) }}">
                            @csrf
                            <button class="link-button">Marcar aprovado</button>
                        </form>
                    @endif
                    @unless($readonly)
                        <form method="POST" action="{{ route('orcamentos.attachments.destroy', [$quote, $attachment]) }}">
                            @csrf @method('DELETE')
                            <button class="link-button danger-text">Remover</button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <p class="empty">Nenhum arquivo técnico nesta versão.</p>
        @endforelse
    </div>
</section>
