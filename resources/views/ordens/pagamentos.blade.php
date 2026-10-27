<section class="card" id="pagamentos">
<div class="osf-card-head"><div><p>Registre os valores recebidos para acompanhar o que falta pagar.</p></div></div>
<div class="osf-payment-totals"><div><span>Total da OS</span><strong>R$ {{ number_format((float)$ordem->valor_total_final,2,',','.') }}</strong></div><div><span>Recebido</span><strong>R$ {{ number_format($ordem->valorPago(),2,',','.') }}</strong></div><div><span>Saldo restante</span><strong>R$ {{ number_format($ordem->saldoRestante(),2,',','.') }}</strong></div></div>
@if($ordem->saldoRestante() > 0)
<form method="POST" action="{{ route('ordens.pagamentos.store',$ordem) }}" class="osf-grid osf-grid-3">
@csrf<input type="hidden" name="chave" value="{{ old('chave', (string) \Illuminate\Support\Str::uuid()) }}">
<div class="osf-field"><label for="pagamento-valor">Valor recebido (R$) *</label><input id="pagamento-valor" name="valor" type="text" inputmode="decimal" data-mask="currency" value="{{ old('valor') }}" required maxlength="18"></div>
<div class="osf-field"><label for="pagamento-data">Data *</label><input id="pagamento-data" name="data_pagamento" type="date" value="{{ old('data_pagamento', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
<div class="osf-field"><label for="pagamento-forma">Forma *</label><select id="pagamento-forma" name="forma" required>@foreach(['pix'=>'Pix','dinheiro'=>'Dinheiro','cartao'=>'Cartão','transferencia'=>'Transferência','outro'=>'Outro'] as $valor=>$label)<option value="{{ $valor }}" @selected(old('forma') === $valor)>{{ $label }}</option>@endforeach</select></div>
<div class="osf-field osf-span-2"><label for="pagamento-observacoes">Observações</label><input id="pagamento-observacoes" name="observacoes" maxlength="500" value="{{ old('observacoes') }}" placeholder="Ex.: sinal ou restante da entrega"></div>
<button class="button fit osf-align-end" type="submit"><x-icon name="check"/>Registrar pagamento</button>
</form>
@else<p class="osf-paid-note"><x-icon name="check"/>OS quitada. Nenhum saldo a receber.</p>@endif
@if($ordem->pagamentos->isNotEmpty())<div class="osf-payment-history"><h3>Histórico de recebimentos</h3>@foreach($ordem->pagamentos as $pagamento)<article><div><strong>R$ {{ number_format((float)$pagamento->valor,2,',','.') }}</strong> · {{ $pagamento->data_pagamento->format('d/m/Y') }} · {{ ['pix'=>'Pix','dinheiro'=>'Dinheiro','cartao'=>'Cartão','transferencia'=>'Transferência','outro'=>'Outro'][$pagamento->forma] ?? $pagamento->forma }}<p>{{ $pagamento->observacoes }}@if($pagamento->usuario) · {{ $pagamento->usuario->name }}@endif</p>@if($pagamento->estornado_em)<small class="osf-help osf-help-error">Estornado: {{ $pagamento->motivo_estorno }}</small>@endif</div>@can('administrar')@if(!$pagamento->estornado_em)<details><summary class="text-link">Estornar</summary><form method="POST" action="{{ route('ordens.pagamentos.estornar',[$ordem,$pagamento]) }}" data-confirm="Estornar este recebimento? O saldo será atualizado.">@csrf @method('PATCH')<label class="sr-only" for="estorno-{{ $pagamento->id }}">Motivo do estorno</label><input id="estorno-{{ $pagamento->id }}" name="motivo_estorno" maxlength="255" placeholder="Motivo do estorno" required><button type="submit" class="text-button danger">Confirmar estorno</button></form></details>@endif
@endcan
</article>@endforeach</div>@endif
</section>
