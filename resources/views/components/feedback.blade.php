@if(session('success'))<div class="notice notice-success" role="status"><x-icon name="check"/><span>{{ session('success') }}</span></div>@endif
@if($errors->any())<div class="notice notice-error" role="alert"><x-icon name="alert"/><div><strong>Confira as informações</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
