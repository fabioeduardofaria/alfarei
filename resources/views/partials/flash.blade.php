@if (session('success'))<div class="flash success">✓ {{ session('success') }}</div>@endif
@if ($errors->any())<div class="flash error">Revise os campos destacados antes de salvar.</div>@endif
