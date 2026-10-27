@extends('layout')
@section('title','Ordens de serviço')
@section('content')
<div class="page-heading"><div><span class="eyebrow">DO ORÇAMENTO À ENTREGA</span><h1>Ordens de serviço</h1><p>Encontre cada pedido e acompanhe a produção.</p></div><a class="button fit" href="{{ route('ordens.create') }}"><x-icon name="plus"/>Nova ordem</a></div>
<section class="card table-card"><form method="GET" class="filter-form" action="{{ route('ordens.index') }}" role="search"><label class="search-field" for="q">Pesquisar por cliente, produto ou OS<div class="input-icon"><x-icon name="search"/><input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="Nome do cliente ou número da OS" maxlength="255"></div></label><label for="status_id">Status<select name="status_id" id="status_id"><option value="">Todos os status</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string)request('status_id') === (string)$status->id)>{{ $status->nome }}</option>@endforeach</select></label><button class="button fit" type="submit">Pesquisar</button>@if(request()->filled('q') || request()->filled('status_id'))<a class="text-link filter-clear" href="{{ route('ordens.index') }}">Limpar</a>@endif</form>
@include('ordens.rows')<x-pagination :paginator="$ordens"/></section>
@endsection
