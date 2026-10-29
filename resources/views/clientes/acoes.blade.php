@php($menuId = 'acoes-clientes-'.$cliente->id)
<button type="button" class="button button-secondary compact fit osf-order-actions-button" popovertarget="{{ $menuId }}">Ações <span aria-hidden="true">⌄</span><span class="sr-only">do cliente {{ $cliente->nome }}</span></button>
<div id="{{ $menuId }}" class="osf-order-menu" popover="auto" data-os-menu>
@can('clientes.visualizar')<a href="{{ route('clientes.show', $cliente) }}"><x-icon name="user"/>Ver cliente</a>@endcan
@can('clientes.editar')<a href="{{ route('clientes.edit', $cliente) }}"><x-icon name="edit"/>Editar cliente</a>@endcan
<div class="osf-menu-divider"></div>
@can('ordens.visualizar')<a href="{{ route('clientes.historico', $cliente) }}" data-os-acao><x-icon name="orders"/>Histórico de ordens de serviço</a>@endcan
</div>
