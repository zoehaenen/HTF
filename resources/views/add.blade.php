@extends('layouts.app')

@section('title', 'Toevoegen')

@section('content')
<h1>Taken toevoegen</h1>
<p class="muted">Plak hier een opdrachtomschrijving, een mail van je docent of cursusinfo. De AI haalt de deadlines eruit en schat hoeveel werk het is. Daarna kan je alles nog nakijken.</p>

@if (session('error'))
<article class="error-box">
  <strong>{{ session('error') }}</strong>
  @if (session('raw'))
  <details>
    <summary>Ruwe tekst</summary>
    <pre>{{ session('raw') }}</pre>
  </details>
  @endif
</article>
@endif

<form method="post" action="{{ route('analyse') }}" onsubmit="this.querySelector('button').setAttribute('aria-busy', 'true'); this.querySelector('button').disabled = true;">
  @csrf
  <label for="text">Tekst</label>
  <textarea id="text" name="text" rows="12" required placeholder="Plak hier je tekst..." @error('text') aria-invalid="true" @enderror>{{ old('text') }}</textarea>
  @error('text')<small class="error-text">{{ $message }}</small>@enderror
  <button type="submit">Analyseer</button>
</form>
@endsection
