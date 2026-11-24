@extends('layouts.app')

@section('content')
<h1>Taak toevoegen</h1>
<p class="small-muted">Plak hier je opdrachtomschrijving, mail van de docent of cursusinfo. De AI zoekt de deadlines eruit.</p>

@if (session('error'))
<article style="border-color: #d33;">
  <header><strong>{{ session('error') }}</strong></header>
  <p>Antwoord van de AI:</p>
  <pre class="small-muted" style="white-space: pre-wrap;">{{ session('raw') }}</pre>
</article>
@endif

<form method="post" action="{{ route('analyse') }}">
  @csrf
  <label for="text">Tekst</label>
  <textarea id="text" name="text" rows="12" required placeholder="Plak hier je tekst...">{{ old('text') }}</textarea>
  <button type="submit">Analyseer</button>
</form>
@endsection
