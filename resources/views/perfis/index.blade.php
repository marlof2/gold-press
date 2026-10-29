@extends('layout')
@section('title', 'Perfis e permissões')
@section('content')
<div class="page-heading"><div><h1>Perfis e permissões</h1><p>As permissões do perfil são aplicadas a todos os usuários vinculados a ele.</p></div></div>
<section class="card table-card"><div class="responsive-table"><table><thead><tr><th>Perfil</th><th>Usuários</th><th>Permissões</th><th>Ações</th></tr></thead><tbody>
@foreach($perfis as $perfil)
<tr><td data-label="Perfil"><span class="record-title">{{ $perfil->nome }}</span><span class="record-subtitle">{{ $perfil->descricao }}</span></td><td data-label="Usuários">{{ $perfil->usuarios_count }}</td><td data-label="Permissões">{{ $perfil->permissoes_count }}</td><td class="row-actions">@can('update', $perfil)<a class="button button-secondary compact fit" href="{{ route('perfis.edit', $perfil) }}"><x-icon name="shield"/>Editar permissões</a>@endcan</td></tr>
@endforeach
</tbody></table></div></section>
@endsection
