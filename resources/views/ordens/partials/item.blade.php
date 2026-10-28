@php
    $tipo = data_get($item, 'tipo', 'unitario');
    $tipo = $tipo instanceof \App\Enums\TipoCobranca ? $tipo->value : $tipo;
    $tipo = in_array($tipo, ['unitario', 'm2'], true) ? $tipo : 'unitario';
    $campo = fn (string $nome, mixed $padrao = null) => old('itens.'.$index.'.'.$nome, data_get($item, $nome, $padrao));
    $quantidade = $campo('quantidade', 1);
    $largura = $campo('largura');
    $altura = $campo('altura');
    $valorUnitario = $campo('valor_unitario', 0);
    $valorM2 = $campo('valor_m2', 0);
    $area = data_get($item, 'area', 0);
    $subtotal = data_get($item, 'subtotal', 0);
    $erro = fn (string $nome) => $errors->has('itens.'.$index.'.'.$nome);
    $id = fn (string $nome) => 'itens-'.$index.'-'.$nome;
@endphp
<article class="osf-item" data-item data-index="{{ $index }}">
<div class="osf-item-top">
<span class="osf-item-numero" data-item-numero>Item</span>
<div class="osf-segmented" role="radiogroup" aria-label="Tipo de cobrança do item">
<label><input type="radio" name="itens[{{ $index }}][tipo]" value="unitario" @checked($tipo === 'unitario') data-tipo-item>Unitário</label>
<label><input type="radio" name="itens[{{ $index }}][tipo]" value="m2" @checked($tipo === 'm2') data-tipo-item>m²</label>
</div>
<div class="osf-item-acoes">
<button type="button" class="osf-icon-button" data-item-duplicar aria-label="Duplicar item" title="Duplicar item"><x-icon name="copy"/></button>
<button type="button" class="osf-icon-button osf-icon-danger" data-item-remover aria-label="Remover item" title="Remover item"><x-icon name="trash"/></button>
</div>
</div>
<div class="osf-item-grid">
<div class="osf-field osf-item-produto">
<label for="{{ $id('descricao') }}">Produto / descrição *</label>
<input id="{{ $id('descricao') }}" type="text" name="itens[{{ $index }}][descricao]" value="{{ $campo('descricao') }}" list="produtos-lista" maxlength="255" required autocomplete="off" placeholder="Busque no catálogo ou digite o produto" data-produto @class(['is-invalid' => $erro('descricao')])>
<input type="hidden" name="itens[{{ $index }}][produto_id]" value="{{ $campo('produto_id') }}" data-produto-id>
@error('itens.'.$index.'.descricao')<small class="osf-error">{{ $message }}</small>@enderror
</div>
<div class="osf-field osf-item-quantidade">
<label for="{{ $id('quantidade') }}">Quantidade *</label>
<input id="{{ $id('quantidade') }}" type="text" inputmode="decimal" name="itens[{{ $index }}][quantidade]" value="{{ $quantidade }}" data-mask="decimal" data-campo="quantidade" maxlength="10" required @class(['is-invalid' => $erro('quantidade')])>
@error('itens.'.$index.'.quantidade')<small class="osf-error">{{ $message }}</small>@enderror
</div>
<div class="osf-item-subtotal">
<span>Subtotal</span>
<output data-subtotal>R$ {{ number_format((float) $subtotal, 2, ',', '.') }}</output>
</div>
</div>
<div class="osf-item-grid osf-item-condicional" data-campos="unitario" @if($tipo !== 'unitario') hidden @endif>
<div class="osf-field">
<label for="{{ $id('valor_unitario') }}">Valor unitário *</label>
<input id="{{ $id('valor_unitario') }}" type="text" inputmode="decimal" name="itens[{{ $index }}][valor_unitario]" value="{{ number_format((float) $valorUnitario, 2, ',', '.') }}" data-mask="currency" data-campo="valor_unitario" data-obrigatorio="1" maxlength="18" @if($tipo !== 'unitario') disabled @else required @endif @class(['is-invalid' => $erro('valor_unitario')])>
@error('itens.'.$index.'.valor_unitario')<small class="osf-error">{{ $message }}</small>@enderror
</div>
</div>
<div class="osf-item-grid osf-item-condicional" data-campos="m2" @if($tipo !== 'm2') hidden @endif>
<div class="osf-field">
<label for="{{ $id('largura') }}">Largura (m) *</label>
<input id="{{ $id('largura') }}" type="text" inputmode="decimal" name="itens[{{ $index }}][largura]" value="{{ $largura !== null ? number_format((float) $largura, 2, ',', '.') : '' }}" data-mask="decimal" data-campo="largura" data-obrigatorio="1" maxlength="10" @if($tipo !== 'm2') disabled @else required @endif @class(['is-invalid' => $erro('largura')])>
@error('itens.'.$index.'.largura')<small class="osf-error">{{ $message }}</small>@enderror
</div>
<div class="osf-field">
<label for="{{ $id('altura') }}">Altura (m) *</label>
<input id="{{ $id('altura') }}" type="text" inputmode="decimal" name="itens[{{ $index }}][altura]" value="{{ $altura !== null ? number_format((float) $altura, 2, ',', '.') : '' }}" data-mask="decimal" data-campo="altura" data-obrigatorio="1" maxlength="10" @if($tipo !== 'm2') disabled @else required @endif @class(['is-invalid' => $erro('altura')])>
@error('itens.'.$index.'.altura')<small class="osf-error">{{ $message }}</small>@enderror
</div>
<div class="osf-item-area">
<span>Área total</span>
<output data-area>{{ number_format((float) $area, 4, ',', '.') }} m²</output>
</div>
<div class="osf-field">
<label for="{{ $id('valor_m2') }}">Valor do m² *</label>
<input id="{{ $id('valor_m2') }}" type="text" inputmode="decimal" name="itens[{{ $index }}][valor_m2]" value="{{ number_format((float) $valorM2, 2, ',', '.') }}" data-mask="currency" data-campo="valor_m2" data-obrigatorio="1" maxlength="18" @if($tipo !== 'm2') disabled @else required @endif @class(['is-invalid' => $erro('valor_m2')])>
@error('itens.'.$index.'.valor_m2')<small class="osf-error">{{ $message }}</small>@enderror
</div>
</div>
<details class="osf-item-detalhes">
<summary>Detalhes adicionais</summary>
<div class="osf-item-detalhes-grid">
<div class="osf-field"><label for="{{ $id('codigo') }}">Código</label><input id="{{ $id('codigo') }}" type="text" name="itens[{{ $index }}][codigo]" value="{{ $campo('codigo') }}" maxlength="255"></div>
<div class="osf-field"><label for="{{ $id('referencia') }}">Referência</label><input id="{{ $id('referencia') }}" type="text" name="itens[{{ $index }}][referencia]" value="{{ $campo('referencia') }}" maxlength="255"></div>
<div class="osf-field"><label for="{{ $id('formato') }}">Formato</label><input id="{{ $id('formato') }}" type="text" name="itens[{{ $index }}][formato]" value="{{ $campo('formato') }}" maxlength="255"></div>
<div class="osf-field"><label for="{{ $id('vias') }}">Vias</label><input id="{{ $id('vias') }}" type="text" name="itens[{{ $index }}][vias]" value="{{ $campo('vias') }}" maxlength="255"></div>
<div class="osf-field"><label for="{{ $id('cores') }}">Cores</label><input id="{{ $id('cores') }}" type="text" name="itens[{{ $index }}][cores]" value="{{ $campo('cores') }}" maxlength="255"></div>
</div>
</details>
</article>
