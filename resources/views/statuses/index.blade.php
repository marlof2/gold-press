@extends('layout')
@section('title','Status das ordens')
@section('content')
<div class="page-heading"><div><span class="eyebrow">SEU FLUXO DE PRODUÇÃO</span><h1>Status das ordens</h1><p>Organize as etapas do pedido do seu jeito.</p></div>@can('statuses.criar')<a class="button fit" href="{{ route('statuses.create') }}"><x-icon name="plus"/>Novo status</a>@endcan</div>
<section class="card table-card"><div class="responsive-table"><table><thead><tr><th>Status</th><th>Tipo</th><th>Posição</th><th>Ordens</th><th>Ações</th></tr></thead><tbody>@foreach($statuses as $status)<tr><td data-label="Status"><x-status-badge :status="$status"/></td><td data-label="Tipo">{{ $status->finalizado ? 'Finaliza a ordem' : 'Em andamento' }}</td><td data-label="Posição">{{ $status->posicao }}</td><td data-label="Ordens">{{ $status->ordens_count }}</td><td class="row-actions">@can('statuses.editar')<a class="text-link" href="{{ route('statuses.edit',$status) }}">Editar <x-icon name="edit"/></a>@endcan
@can('statuses.excluir')<form method="POST" action="{{ route('statuses.destroy',$status) }}" data-confirm="Excluir o status {{ $status->nome }}?">@csrf @method('DELETE')<button class="text-button danger" @disabled($status->ordens_count > 0) title="{{ $status->ordens_count ? 'Status utilizado em ordens' : 'Excluir status' }}">Excluir</button></form>@endcan
</td></tr>@endforeach</tbody></table></div><x-pagination :paginator="$statuses"/></section>
@endsection
