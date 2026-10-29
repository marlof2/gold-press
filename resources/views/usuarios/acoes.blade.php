@canany(['usuarios.editar', 'usuarios.excluir'])
@php($menuId = 'acoes-usuarios-'.$usuario->id)
<button type="button" class="button button-secondary compact fit osf-order-actions-button" popovertarget="{{ $menuId }}">Ações <span aria-hidden="true">⌄</span><span class="sr-only">do usuário {{ $usuario->name }}</span></button>
<div id="{{ $menuId }}" class="osf-order-menu" popover="auto" data-os-menu>
@can('usuarios.editar')<a href="{{ route('usuarios.edit', $usuario) }}"><x-icon name="edit"/>Editar usuário</a>@endcan
<div class="osf-menu-divider"></div>
@can('usuarios.excluir')<form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" data-confirm="Excluir o usuário {{ $usuario->name }}?">@csrf @method('DELETE')<button type="submit" class="danger" @disabled($usuario->is(auth()->user())) title="{{ $usuario->is(auth()->user()) ? 'Você não pode excluir a própria conta' : 'Excluir usuário' }}"><x-icon name="trash"/>Excluir usuário</button></form>@endcan
</div>

@endcanany
