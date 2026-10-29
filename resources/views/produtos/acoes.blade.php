@canany(['produtos.editar', 'produtos.excluir'])
@php($menuId = 'acoes-produtos-'.$produto->id)
<button type="button" class="button button-secondary compact fit osf-order-actions-button" popovertarget="{{ $menuId }}">Ações <span aria-hidden="true">⌄</span><span class="sr-only">do produto {{ $produto->nome }}</span></button>
<div id="{{ $menuId }}" class="osf-order-menu" popover="auto" data-os-menu>
@can('produtos.editar')<a href="{{ route('produtos.edit', $produto) }}"><x-icon name="edit"/>Editar produto</a>@endcan
<div class="osf-menu-divider"></div>
@can('produtos.excluir')<form method="POST" action="{{ route('produtos.destroy', $produto) }}" data-confirm="Excluir o produto {{ $produto->nome }}?">@csrf @method('DELETE')<button type="submit" class="danger" @disabled($produto->itens_count > 0) title="{{ $produto->itens_count > 0 ? 'Produto usado em OS: desative em vez de excluir' : 'Excluir produto' }}"><x-icon name="trash"/>Excluir produto</button></form>@endcan
</div>

@endcanany
