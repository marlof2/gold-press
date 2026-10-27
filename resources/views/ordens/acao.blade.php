<div class="osf-action-heading"><div><small>OS #{{ str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) }} · {{ $ordem->nome }}</small><h2 id="os-acao-titulo">{{ ['status'=>'Alterar status','whatsapp'=>'Mensagem para o cliente','pagamentos'=>'Pagamentos e saldo'][$acao] }}</h2></div><button class="icon-button" type="button" data-os-acao-fechar aria-label="Fechar ação"><x-icon name="close"/></button></div>
<div class="notice notice-error" data-os-acao-erros role="alert" hidden></div>
<div class="osf-action-panel">
@if($acao === 'status')
<form method="POST" action="{{ route('ordens.status',$ordem) }}">
@csrf @method('PATCH')
<p class="hint">Atualize o andamento desta ordem de serviço.</p>
<div class="osf-field"><label for="status-acao">Novo status</label><select id="status-acao" name="status_id" required>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected($ordem->status_id === $status->id)>{{ $status->nome }}</option>@endforeach</select></div>
<div class="form-actions"><button class="button button-secondary fit" type="button" data-os-acao-fechar>Cancelar</button><button class="button fit" type="submit"><x-icon name="check"/>Atualizar status</button></div>
</form>
@elseif($acao === 'whatsapp')
@include('ordens.whatsapp')
@else
@include('ordens.pagamentos')
@endif
</div>
