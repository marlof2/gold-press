@extends('layout')
@section('title',$produto->exists ? 'Editar produto' : 'Novo produto')
@push('scripts')
<script>
(() => {
    const tipo = document.getElementById('tipo');
    const grupos = document.querySelectorAll('[data-grupo-valor]');
    const atualizar = () => grupos.forEach((grupo) => {
        const ativo = grupo.dataset.grupoValor === tipo.value;
        grupo.hidden = !ativo;
        grupo.querySelectorAll('input').forEach((campo) => { campo.disabled = !ativo; });
    });
    tipo?.addEventListener('change', atualizar);
    atualizar();
})();
</script>
@endpush
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('produtos.index') }}">← Produtos</a><h1>{{ $produto->exists ? 'Editar produto' : 'Novo produto' }}</h1><p>Defina como o produto é cobrado e seu valor padrão.</p></div></div>
<section class="card form-card">
<form method="POST" action="{{ $produto->exists ? route('produtos.update',$produto) : route('produtos.store') }}">@csrf @if($produto->exists) @method('PUT') @endif
<div class="fields">
<label class="wide" for="nome">Nome do produto *<input id="nome" name="nome" type="text" value="{{ old('nome',$produto->nome) }}" maxlength="255" required placeholder="Ex.: Banner em lona"></label>
<label for="tipo">Tipo de cobrança *<select id="tipo" name="tipo" required><option value="unitario" @selected(old('tipo',$produto->tipo?->value ?? 'unitario') === 'unitario')>Unitário</option><option value="m2" @selected(old('tipo',$produto->tipo?->value) === 'm2')>Metro quadrado (m²)</option></select></label>
<label for="unidade">Unidade<input id="unidade" name="unidade" type="text" value="{{ old('unidade',$produto->unidade) }}" maxlength="20" placeholder="un, m², pç"></label>
<div class="wide" data-grupo-valor="unitario" @if(old('tipo',$produto->tipo?->value ?? 'unitario') !== 'unitario') hidden @endif><label for="valor_unitario">Valor unitário padrão (R$)<input id="valor_unitario" name="valor_unitario" type="text" inputmode="decimal" value="{{ number_format((float)old('valor_unitario',$produto->valor_unitario ?? 0),2,',','.') }}" data-mask="currency" maxlength="18" @if(old('tipo',$produto->tipo?->value ?? 'unitario') !== 'unitario') disabled @endif></label></div>
<div class="wide" data-grupo-valor="m2" @if(old('tipo',$produto->tipo?->value) !== 'm2') hidden @endif><label for="valor_m2">Valor padrão por m² (R$)<input id="valor_m2" name="valor_m2" type="text" inputmode="decimal" value="{{ number_format((float)old('valor_m2',$produto->valor_m2 ?? 0),2,',','.') }}" data-mask="currency" maxlength="18" @if(old('tipo',$produto->tipo?->value) !== 'm2') disabled @endif></label></div>
<label class="checkbox wide"><input type="checkbox" name="ativo" value="1" @checked(old('ativo',$produto->ativo ?? true))>Produto ativo no catálogo</label>
<p class="hint wide">Produtos inativos não aparecem no autocomplete da OS, mas continuam nos itens já criados.</p>
</div>
<div class="form-actions"><a class="button button-secondary fit" href="{{ route('produtos.index') }}">Cancelar</a><button class="button fit" type="submit">{{ $produto->exists ? 'Salvar alterações' : 'Cadastrar produto' }} <x-icon name="check"/></button></div>
</form>
</section>
@endsection
