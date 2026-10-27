<dialog id="cliente-rapido" class="osf-dialog" aria-labelledby="cliente-rapido-titulo">
<form method="POST" action="{{ route('clientes.store') }}" id="cliente-rapido-form">
@csrf
<div class="osf-card-head"><div><h2 id="cliente-rapido-titulo">Cadastrar cliente</h2><p>O cliente será selecionado nesta OS ao salvar.</p></div><button class="icon-button" type="button" data-cliente-fechar aria-label="Fechar cadastro"><x-icon name="close"/></button></div>
<div class="notice notice-error" data-cliente-erros role="alert" hidden></div>
<div class="osf-grid osf-grid-3">
<div class="osf-field osf-span-3"><label for="rapido-nome">Nome / razão social *</label><input id="rapido-nome" name="nome" maxlength="255" required autocomplete="name"></div>
<div class="osf-field"><label for="rapido-documento">CPF / CNPJ</label><input id="rapido-documento" name="documento" data-mask="cpf-cnpj" maxlength="18" inputmode="numeric"></div>
<div class="osf-field"><label for="rapido-telefone">WhatsApp com DDD *</label><input id="rapido-telefone" name="telefone" type="tel" data-mask="telefone" maxlength="20" required autocomplete="tel"></div>
<div class="osf-field"><label for="rapido-email">E-mail</label><input id="rapido-email" name="email" type="email" maxlength="255" autocomplete="email"></div>
<div class="osf-field"><label for="rapido-cep">CEP</label><div class="osf-input-status"><input id="rapido-cep" name="cep" data-mask="cep" data-viacep maxlength="9" inputmode="numeric" placeholder="00000-000"><span class="osf-spinner" data-cep-spinner hidden aria-hidden="true"></span></div><small class="osf-help" data-cep-feedback role="status" aria-live="polite"></small></div>
<div class="osf-field osf-span-2"><label for="rapido-endereco">Logradouro</label><input id="rapido-endereco" name="endereco" maxlength="255" autocomplete="address-line1"></div>
<div class="osf-field"><label for="rapido-numero">Número</label><input id="rapido-numero" name="numero_endereco" maxlength="20" autocomplete="address-line2"></div>
<div class="osf-field osf-span-2"><label for="rapido-bairro">Bairro</label><input id="rapido-bairro" name="bairro" maxlength="255"></div>
<div class="osf-field osf-span-2"><label for="rapido-cidade">Cidade</label><input id="rapido-cidade" name="cidade" maxlength="255" autocomplete="address-level2"></div>
<div class="osf-field"><label for="rapido-uf">UF</label><select id="rapido-uf" name="uf"><option value="">—</option>@foreach(config('ufs') as $sigla => $nome)<option value="{{ $sigla }}">{{ $sigla }}</option>@endforeach</select></div>
</div>
<div class="form-actions"><button class="button button-secondary fit" type="button" data-cliente-fechar>Cancelar</button><button class="button fit" type="submit">Cadastrar e selecionar <x-icon name="check"/></button></div>
</form></dialog>
