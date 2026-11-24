@extends('layouts.app')

@section('title', 'Bevestigen')

@section('content')
<h1>Taken bevestigen</h1>
<p class="muted">Kijk de gevonden taken na en pas aan waar nodig. Alleen aangevinkte taken worden opgeslagen.</p>

@if ($errors->any())
<p class="error-text">Niet alles is correct ingevuld. Verbeter de rood gemarkeerde velden.</p>
@endif

<form method="post" action="{{ route('store') }}">
  @csrf
  <div class="overflow-auto">
  <table>
    <thead>
      <tr>
        <th>Opslaan</th>
        <th>Titel</th>
        <th>Vak</th>
        <th>Deadline</th>
        <th>Uren</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($draft as $i => $t)
      @php
        $deadline = old("tasks.$i.deadline", $t['deadline']);
        $badDate = $errors->has("tasks.$i.deadline") || !\App\Services\AnthropicService::validDate($deadline);
        $save = old() ? old("tasks.$i.save") : true;
      @endphp
      <tr>
        <td><input type="checkbox" name="tasks[{{ $i }}][save]" value="1" @checked($save) aria-label="Opslaan"></td>
        <td>
          <input type="text" name="tasks[{{ $i }}][title]" value="{{ old("tasks.$i.title", $t['title']) }}" maxlength="255" @error("tasks.$i.title") aria-invalid="true" @enderror>
        </td>
        <td><input type="text" name="tasks[{{ $i }}][course]" value="{{ old("tasks.$i.course", $t['course']) }}" maxlength="255" placeholder="Geen vak"></td>
        <td>
          <input type="date" name="tasks[{{ $i }}][deadline]" value="{{ $badDate ? '' : $deadline }}" @if ($badDate) aria-invalid="true" class="invalid-date" @endif>
          @if ($badDate)<small class="error-text">Datum ontbreekt of is ongeldig</small>@endif
        </td>
        <td><input type="number" name="tasks[{{ $i }}][estimated_hours]" value="{{ old("tasks.$i.estimated_hours", $t['estimated_hours']) }}" step="0.5" min="0.5" max="500"></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  <div class="actions">
    <button type="submit">Opslaan</button>
    <a href="{{ route('create') }}" role="button" class="secondary outline">Opnieuw analyseren</a>
  </div>
</form>
@endsection
