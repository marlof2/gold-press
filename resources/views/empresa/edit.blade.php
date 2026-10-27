@extends('layout')
@section('title','Dados da empresa')
@section('content')
<div class="page-heading"><div><span class="eyebrow">IDENTIDADE DA GRÁFICA</span><h1>Dados da empresa</h1><p>Essas informações aparecem no cabeçalho do documento da OS.</p></div></div>
<section class="card form-card">
<form method="POST" action="{{ route('empresa.update') }}">@csrf @method('PUT')
<div class="fields">
<label class="wide" for="nome">Nome da empresa *<input id="nome" name="nome" type="text" value="{{ old('nome',$empresa->nome) }}" maxlength="255" required></label>
<label class="" for="razao_social">Razão social<input id="razao_social" name="razao_social" type="text" value="{{ old('razao_social',$empresa->razao_social) }}" maxlength="255"></label>
<label class="" for="documento">CNPJ<input id="documento" name="documento" type="text" data-mask="cpf-cnpj" value="{{ old('documento',$empresa->documento) }}" maxlength="20" placeholder="00.000.000/0000-00"></label>
<label class="wide" for="endereco">Endereço completo<input id="endereco" name="endereco" type="text" value="{{ old('endereco',$empresa->endereco) }}" maxlength="255"></label>
<label class="" for="telefone">Telefone<input id="telefone" name="telefone" type="text" data-mask="telefone" value="{{ old('telefone',$empresa->telefone) }}" maxlength="30" placeholder="(71) 90000-0000"></label>
<label class="" for="email">E-mail<input id="email" name="email" type="email" value="{{ old('email',$empresa->email) }}" maxlength="255"></label>
<label class="wide" for="site">Site<input id="site" name="site" type="text" value="{{ old('site',$empresa->site) }}" maxlength="255"></label>
</div>
<div class="form-actions"><button class="button fit" type="submit">Salvar dados <x-icon name="check"/></button></div>
</form>
</section>
@endsection
