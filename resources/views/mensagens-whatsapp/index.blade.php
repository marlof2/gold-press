@extends('layout')
@section('title', 'Mensagens do WhatsApp')
@section('content')
<div class="page-heading"><div><span class="eyebrow">COMUNICAÇÃO COM O CLIENTE</span><h1>Mensagens do WhatsApp</h1><p>Crie e personalize os modelos disponíveis nas ordens de serviço.</p></div>@can('mensagens-whatsapp.criar')<a class="button fit" href="{{ route('mensagens-whatsapp.create') }}"><x-icon name="plus"/>Nova mensagem</a>@endcan</div>
<section class="card table-card"><div class="responsive-table"><table><thead><tr><th>Mensagem</th><th>Posição</th><th>Ações</th></tr></thead><tbody>
@forelse($modelos as $modelo)
<tr><td data-label="Mensagem">{{ $modelo->nome }}</td><td data-label="Posição">{{ $modelo->posicao }}</td><td class="row-actions">@can('mensagens-whatsapp.editar')<a class="text-link" href="{{ route('mensagens-whatsapp.edit', $modelo) }}">Editar <x-icon name="edit"/></a>@endcan</td></tr>
@empty
<tr><td colspan="3">Nenhuma mensagem cadastrada.</td></tr>
@endforelse
</tbody></table></div><x-pagination :paginator="$modelos"/></section>
@endsection
