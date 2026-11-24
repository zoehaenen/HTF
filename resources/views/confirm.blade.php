@extends('layouts.app')

@section('content')
<h1>Bevestig taken</h1>
<p class="small-muted">Pas de gevonden taken aan. Alleen aangevinkte taken worden opgeslagen.</p>

<form method="post" action="{{ route('store') }}">
  @csrf
  <table>
    <thead>
      <tr>
        <th>Opslaan</th>
        <th>Titel</th>
        <th>Vak</th>
        <th>Deadline</th>
        <th>Geschatte uren</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($draft as $i => $t)
      <tr>
        <td><input type="checkbox" name="tasks[{{ $i }}][save]" value="1" checked></td>
        <td><input type="text" name="tasks[{{ $i }}][title]" value="{{ $t['title'] }}" required></td>
        <td><input type="text" name="tasks[{{ $i }}][course]" value="{{ $t['course'] }}" placeholder="geen vak"></td>
        <td>
          <input type="date" name="tasks[{{ $i }}][deadline]" value="{{ $t['deadline'] }}" class="{{ $t['deadline'] ? '' : 'invalid-date' }}">
          @if (!$t['deadline'])
          <p class="invalid-date small-muted">Ongeldige of ontbrekende datum</p>
          @endif
        </td>
        <td><input type="number" name="tasks[{{ $i }}][estimated_hours]" value="{{ $t['estimated_hours'] }}" step="0.5" min="0.5" max="200" required></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <input type="hidden" name="source_text" value="{{ session('source_text') }}">
  <button type="submit">Opslaan</button>
</form>
@endsection
