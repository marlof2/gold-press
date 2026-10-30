<section class="card" id="whatsapp" data-whatsapp-editor data-telefone="{{ $telefoneWhatsApp }}">
<div class="osf-card-head"><div><p>Escolha o aviso, revise o texto e abra a conversa no WhatsApp.</p></div></div>
@can('mensagens-whatsapp.visualizar')<p><a class="text-link" href="{{ route('mensagens-whatsapp.index') }}">Gerenciar mensagens</a></p>@endcan
<div class="osf-field"><label for="whatsapp-modelo">Tipo de mensagem</label><select id="whatsapp-modelo" data-whatsapp-modelo>@foreach($nomesWhatsApp as $chave => $nome)<option value="{{ $chave }}" @selected($chave === 'pedido')>{{ $nome }}</option>@endforeach</select></div>
<div class="osf-field osf-message-field"><label for="whatsapp-mensagem">Mensagem pronta para envio</label><textarea id="whatsapp-mensagem" rows="10" maxlength="6000" data-whatsapp-mensagem>{{ $modelosWhatsApp['pedido'] }}</textarea></div>
<div class="osf-message-actions"><button class="button button-secondary fit" type="button" data-whatsapp-copiar><x-icon name="copy"/>Copiar mensagem</button>@if($telefoneWhatsApp)<a class="button whatsapp fit" href="{{ $whatsappUrl }}" data-whatsapp-abrir target="_blank" rel="noopener noreferrer"><x-icon name="chat"/>Abrir WhatsApp</a>@endif</div><small class="osf-help" data-whatsapp-feedback role="status">{{ $telefoneWhatsApp ? 'A mensagem será aberta para sua confirmação no WhatsApp.' : 'Cliente sem telefone cadastrado. Você pode copiar a mensagem ou adicionar um telefone no cadastro.' }}</small>
<script type="application/json" data-whatsapp-modelos>@json($modelosWhatsApp)</script>
</section>
