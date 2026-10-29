@extends('layout')
@section('title', 'Permissões do perfil')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('perfis.index') }}">← Perfis e permissões</a><h1>{{ $perfil->nome }}</h1><p>Marque as ações que os usuários deste perfil podem executar.</p></div></div>
<form method="POST" action="{{ route('perfis.update', $perfil) }}">
@csrf @method('PUT')
@error('permissoes')<div class="notice notice-error" role="alert">{{ $message }}</div>@enderror
@error('permissoes.*')<div class="notice notice-error" role="alert">{{ $message }}</div>@enderror
@foreach($grupos as $grupo => $permissoes)
<fieldset class="card"><legend><strong>{{ config('permissoes.'.$grupo.'.nome', $grupo) }}</strong></legend><div class="osf-grid osf-grid-3">
@foreach($permissoes as $permissao)
<label class="checkbox"><input type="checkbox" name="permissoes[]" value="{{ $permissao->id }}" @checked(in_array($permissao->id, old('permissoes', $errors->any() ? [] : $perfil->permissoes->modelKeys())))>{{ $permissao->nome }}</label>
@endforeach
</div></fieldset>
@endforeach
<div class="form-actions"><a class="button button-secondary fit" href="{{ route('perfis.index') }}">Cancelar</a><button class="button fit" type="submit">Salvar permissões <x-icon name="check"/></button></div>
</form>
@endsection
