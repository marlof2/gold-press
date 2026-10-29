@extends('layout')
@section('title',$usuario->exists ? 'Editar usuário' : 'Novo usuário')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('usuarios.index') }}">← Usuários</a><h1>{{ $usuario->exists ? 'Editar usuário' : 'Novo usuário' }}</h1><p>Defina os dados de acesso e o perfil deste usuário.</p></div></div>
<section class="card form-card">
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update',$usuario) : route('usuarios.store') }}">@csrf @if($usuario->exists) @method('PUT') @endif
<div class="fields">
<label class="" for="name">Nome *<input id="name" name="name" type="text" value="{{ old('name',$usuario->name) }}" maxlength="255" required autocomplete="name"></label>
<label class="" for="email">E-mail *<input id="email" name="email" type="email" value="{{ old('email',$usuario->email) }}" maxlength="255" required autocomplete="username"></label>
<label class="wide" for="perfil_id">Perfil *<select name="perfil_id" id="perfil_id" required @disabled(!auth()->user()->can('usuarios.atribuir_perfil'))><option value="">Escolha um perfil</option>@foreach($perfis as $perfil)<option value="{{ $perfil->id }}" @selected((string)old('perfil_id',$usuario->perfil_id) === (string)$perfil->id)>{{ $perfil->nome }}</option>@endforeach</select>@cannot('usuarios.atribuir_perfil')<input type="hidden" name="perfil_id" value="{{ $usuario->perfil_id }}"><small>Seu perfil não permite alterar o perfil dos usuários.</small>@endcannot</label>
<label class="" for="password">Senha {{ $usuario->exists ? '' : '*' }}<input id="password" name="password" type="password" autocomplete="new-password" @required(! $usuario->exists)><small>{{ $usuario->exists ? 'Deixe em branco para manter a senha atual.' : 'Defina a senha que preferir.' }}</small></label>
<label class="" for="password_confirmation">Confirme a senha<input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></label>
<p class="hint wide">Ao trocar a senha de outro usuário, as sessões abertas dele são encerradas.</p>
</div>
<div class="form-actions"><a class="button button-secondary fit" href="{{ route('usuarios.index') }}">Cancelar</a><button class="button fit" type="submit">{{ $usuario->exists ? 'Salvar alterações' : 'Cadastrar usuário' }} <x-icon name="check"/></button></div>
</form>
</section>
@endsection
