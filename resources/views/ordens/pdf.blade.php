<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>OS #{{ str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) }}</title>
<style>
@page { margin: 30px; } body { font: 10px 'DejaVu Sans', sans-serif; color: #27372f; } h1 { font-size: 20px; margin: 0 0 6px; } h2 { font-size: 12px; border-bottom: 1px solid #cbd5c4; padding-bottom: 7px; margin-top: 22px; } p { line-height: 1.6; } .muted { color: #65715f; } table { width: 100%; border-collapse: collapse; table-layout: fixed; } th { background: #edf2ed; text-align: left; } td, th { padding: 9px 6px; border-bottom: 1px solid #e5e9e3; overflow-wrap: break-word; } .right { text-align: right; } .total { font-size: 15px; font-weight: bold; } .notes { white-space: pre-wrap; overflow-wrap: break-word; } tr { page-break-inside: avoid; } thead { display: table-header-group; }
</style></head><body>
<h1>{{ $empresa->nome }}</h1><p class="muted">{{ $empresa->documento }} · {{ $empresa->telefone }}<br>{{ $empresa->endereco }}</p>
<h2>ORDEM DE SERVIÇO #{{ str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) }}</h2>
<p><strong>Cliente:</strong> {{ $ordem->nome }}<br><strong>CPF / CNPJ:</strong> {{ $ordem->documento ?: '—' }}<br><strong>Telefone:</strong> {{ $ordem->telefone }}<br><strong>Endereço:</strong> {{ collect([$ordem->endereco,$ordem->numero_endereco,$ordem->bairro,$ordem->cidade,$ordem->uf,$ordem->cep])->filter()->join(' · ') }}</p>
<p><strong>Responsável da gráfica:</strong> {{ $ordem->responsavel ?: 'Não definido' }}</p>
<p><strong>Status:</strong> {{ $ordem->status?->nome }} · <strong>Emissão:</strong> {{ $ordem->created_at->format('d/m/Y') }} · <strong>Entrega prevista:</strong> {{ $ordem->data_entrega?->format('d/m/Y') ?? 'A combinar' }}</p>
<table><thead><tr><th style="width:38%">Produto / serviço</th><th style="width:12%">Qtd.</th><th style="width:18%">Medidas</th><th style="width:16%">Valor</th><th style="width:16%">Subtotal</th></tr></thead><tbody>
@foreach($ordem->itens as $item)<tr><td>{{ $item->descricao }}@if($item->formato || $item->cores)<br><span class="muted">{{ collect([$item->formato,$item->cores])->filter()->join(' · ') }}</span>@endif</td><td>{{ rtrim(rtrim(number_format((float)$item->quantidade,4,',','.'),'0'),',') }} {{ $item->unidade }}</td><td>{{ $item->medidas() ?: '—' }}</td><td>R$ {{ number_format((float)($item->tipo->isMetroQuadrado() ? $item->valor_m2 : $item->valor_unitario),2,',','.') }}{{ $item->tipo->isMetroQuadrado() ? '/m²' : '' }}</td><td class="right">R$ {{ number_format((float)$item->subtotal,2,',','.') }}</td></tr>@endforeach
</tbody></table>
<p class="right">Subtotal: R$ {{ number_format((float)$ordem->valor_total,2,',','.') }}<br>Desconto: R$ {{ number_format((float)$ordem->desconto,2,',','.') }}<br><span class="total">Total: R$ {{ number_format((float)$ordem->valor_total_final,2,',','.') }}</span><br>Recebido: R$ {{ number_format($ordem->valorPago(),2,',','.') }}<br><strong>Saldo: R$ {{ number_format($ordem->saldoRestante(),2,',','.') }}</strong></p>
@if($ordem->condicoes_pagamento)<h2>Condições de pagamento</h2><p class="notes">{{ $ordem->condicoes_pagamento }}</p>@endif
@if($ordem->observacoes)<h2>Observações</h2><p class="notes">{{ $ordem->observacoes }}</p>@endif
@if($ordem->servico_realizado)<h2>Serviço realizado</h2><p class="notes">{{ $ordem->servico_realizado }}</p>@endif
<p class="muted">Documento atualizado em {{ now()->format('d/m/Y H:i') }} · {{ $empresa->nome }}</p>
</body></html>
