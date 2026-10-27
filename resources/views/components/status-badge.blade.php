@props(['status'])
<span class="status-badge" style="--status-color: {{ $status?->cor ?? '#64748b' }}"><i></i>{{ $status?->nome ?? 'Sem status' }}</span>
