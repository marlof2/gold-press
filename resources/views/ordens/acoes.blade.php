@php($menuId = 'acoes-'.($contexto ?? 'ordens').'-'.$item->id)
<button type="button" class="button button-secondary compact fit osf-order-actions-button" popovertarget="{{ $menuId }}">Ações <span aria-hidden="true">⌄</span><span class="sr-only">da OS {{ $item->id }}</span></button>
<div id="{{ $menuId }}" class="osf-order-menu" popover="auto" data-os-menu>
@can('ordens.visualizar')<a href="{{ route('ordens.success',$item->token) }}"><x-icon name="orders"/>Ver ordem</a>@endcan
@can('ordens.editar')<a href="{{ route('ordens.edit',$item) }}"><x-icon name="edit"/>Editar ordem</a>@endcan
<div class="osf-menu-divider"></div>
@can('ordens.status')<a href="{{ route('ordens.acao',[$item,'status']) }}" data-os-acao><x-icon name="status"/>Alterar status</a>@endcan
<a href="{{ route('ordens.acao',[$item,'whatsapp']) }}" data-os-acao><x-icon name="chat"/>Mensagem / WhatsApp</a>
@can('pagamentos.visualizar')<a href="{{ route('ordens.acao',[$item,'pagamentos']) }}" data-os-acao><x-icon name="payment"/>Pagamentos e saldo</a>@endcan
@if($mapsUrl = $item->urlMaps())
<a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" data-os-maps><x-icon name="map"/>Abrir no Maps</a>
@endif
<div class="osf-menu-divider"></div>
@can('ordens.criar')<a href="{{ route('ordens.create',['repetir'=>$item->id]) }}"><x-icon name="copy"/>Repetir OS</a>@endcan
<a href="{{ route('documentos.show',['token'=>$item->token,'formato'=>'pdf','download'=>1]) }}"><x-icon name="download"/>Baixar PDF</a>
<a href="{{ route('documentos.show',['token'=>$item->token,'formato'=>'docx']) }}"><x-icon name="download"/>Baixar Word</a>
@can('ordens.excluir')
<div class="osf-menu-divider"></div>
<form method="POST" action="{{ route('ordens.destroy',$item) }}" data-confirm="Excluir permanentemente esta OS?">@csrf @method('DELETE')<button type="submit" class="danger"><x-icon name="trash"/>Excluir ordem</button></form>
@endcan
</div>
