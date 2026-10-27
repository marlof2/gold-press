<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#233b30">
<title>@yield('title', 'Painel') · Gold Press</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/os-form.css') }}?v={{ filemtime(public_path('css/os-form.css')) }}">
<script src="{{ asset('js/masks.js') }}?v={{ filemtime(public_path('js/masks.js')) }}" defer></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body class="@auth app-body @else guest-body @endauth">
@auth
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<button class="sidebar-backdrop" id="sidebar-backdrop" aria-label="Fechar menu" hidden></button>
<aside class="sidebar" id="sidebar" aria-label="Menu principal">
<a class="brand" href="{{ route('dashboard') }}"><span class="logo">G<span>✦</span></span><span>GOLD PRESS<small>GESTÃO DA GRÁFICA</small></span></a>
<button class="icon-button sidebar-close" id="sidebar-close" aria-label="Fechar menu"><x-icon name="close"/></button>
<div class="sidebar-label">ESPAÇO DE TRABALHO</div>
<nav class="main-nav">
<a href="{{ route('dashboard') }}" @class(['active'=>request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="grid"/>Visão geral</a>
<a href="{{ route('ordens.index') }}" @class(['active'=>request()->routeIs('ordens.*')]) @if(request()->routeIs('ordens.*')) aria-current="page" @endif><x-icon name="orders"/>Ordens de serviço</a>
<a href="{{ route('clientes.index') }}" @class(['active'=>request()->routeIs('clientes.*')]) @if(request()->routeIs('clientes.*')) aria-current="page" @endif><x-icon name="users"/>Clientes</a>
<a href="{{ route('statuses.index') }}" @class(['active'=>request()->routeIs('statuses.*')]) @if(request()->routeIs('statuses.*')) aria-current="page" @endif><x-icon name="status"/>Status das ordens</a>
</nav>
<div class="sidebar-label">ADMINISTRAÇÃO</div>
<nav class="main-nav">
<a href="{{ route('usuarios.index') }}" @class(['active'=>request()->routeIs('usuarios.*')]) @if(request()->routeIs('usuarios.*')) aria-current="page" @endif><x-icon name="shield"/>Usuários</a>
<a href="{{ route('empresa.edit') }}" @class(['active'=>request()->routeIs('empresa.*')]) @if(request()->routeIs('empresa.*')) aria-current="page" @endif><x-icon name="building"/>Empresa</a>
<a href="{{ route('produtos.index') }}" @class(['active'=>request()->routeIs('produtos.*')]) @if(request()->routeIs('produtos.*')) aria-current="page" @endif><x-icon name="box"/>Produtos</a>
</nav>
<div class="sidebar-bottom"><a class="account-link" href="{{ route('account') }}"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>Minha conta e senha</small></span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><x-icon name="logout"/>Sair da conta</button></form></div>
</aside>
<div class="app-shell"><header class="topbar"><div class="topbar-left"><button class="icon-button menu-toggle" id="menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar"><x-icon name="menu"/></button><span class="topbar-section">@yield('title', 'Visão geral')</span><span class="topbar-divider">/</span><span class="topbar-caption">Seu espaço de produção</span></div><div class="topbar-actions"><form class="topbar-search" action="{{ route('ordens.index') }}" role="search"><x-icon name="search"/><input type="search" name="q" placeholder="Buscar OS ou cliente" aria-label="Buscar OS ou cliente" maxlength="255"></form></div></header>
<main id="conteudo" tabindex="-1"><x-feedback/>@yield('content')</main><footer>Gold Press <span>•</span> Organização em cada impressão</footer></div>
<nav class="mobile-nav" aria-label="Atalhos"><a href="{{ route('dashboard') }}" @class(['active'=>request()->routeIs('dashboard')])><x-icon name="grid"/><span>Início</span></a><a href="{{ route('ordens.index') }}" @class(['active'=>request()->routeIs('ordens.*')])><x-icon name="orders"/><span>Ordens</span></a><a href="{{ route('clientes.index') }}" @class(['active'=>request()->routeIs('clientes.*')])><x-icon name="users"/><span>Clientes</span></a><a href="{{ route('statuses.index') }}" @class(['active'=>request()->routeIs('statuses.*')])><x-icon name="status"/><span>Status</span></a></nav>
@else
<main class="guest-main" id="conteudo">@yield('content')</main>
@endauth
@stack('scripts')
</body>
</html>
