<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deadline Radar</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<link rel="stylesheet" href="/css/app.css">
@stack('scripts')
</head>
<body>
<main class="container">
<nav>
  <ul>
    <li><strong>Deadline Radar</strong></li>
  </ul>
  <ul>
    <li><a href="{{ route('create') }}">Toevoegen</a></li>
    <li><a href="{{ route('confirm') }}">Bevestigen</a></li>
    <li><a href="{{ route('overview') }}">Overzicht</a></li>
    <li><a href="{{ route('settings') }}">Instellingen</a></li>
  </ul>
</nav>
@if (session('status'))
<p role="status">{{ session('status') }}</p>
@endif
@yield('content')
</main>
</body>
</html>
