<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Deadline Radar') - Deadline Radar</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="container">
<nav>
  <ul>
    <li><a href="{{ route('overview') }}" class="brand"><strong>Deadline Radar</strong></a></li>
  </ul>
  <ul>
    <li><a href="{{ route('create') }}" @if (request()->routeIs('create')) aria-current="page" @endif>Toevoegen</a></li>
    <li><a href="{{ route('confirm') }}" @if (request()->routeIs('confirm')) aria-current="page" @endif>Bevestigen</a></li>
    <li><a href="{{ route('overview') }}" @if (request()->routeIs('overview')) aria-current="page" @endif>Overzicht</a></li>
    <li><a href="{{ route('settings') }}" @if (request()->routeIs('settings')) aria-current="page" @endif>Instellingen</a></li>
  </ul>
</nav>
</header>
<main class="container">
@if (session('status'))
<p class="notice" role="status">{{ session('status') }}</p>
@endif
@yield('content')
</main>
@stack('scripts')
</body>
</html>
