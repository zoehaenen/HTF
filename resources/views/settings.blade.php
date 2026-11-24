@extends('layouts.app')

@section('title', 'Instellingen')

@section('content')
<h1>Instellingen</h1>
<p class="muted">Hoeveel uren kan je per weekdag aan schoolwerk besteden? De planning verdeelt je taken volgens deze uren.</p>

@if ($errors->any())
<p class="error-text">{{ $errors->first() }}</p>
@endif

<form method="post" action="{{ route('settings.update') }}">
  @csrf
  <table>
    <thead>
      <tr>
        <th>Dag</th>
        <th>Uren</th>
      </tr>
    </thead>
    <tbody>
      @foreach (['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'] as $w => $dag)
      <tr>
        <td><label for="hours-{{ $w }}">{{ $dag }}</label></td>
        <td><input id="hours-{{ $w }}" type="number" name="hours[{{ $w }}]" value="{{ old('hours.' . $w, $hours[$w] + 0) }}" step="0.5" min="0" max="24" required @error('hours.' . $w) aria-invalid="true" @enderror></td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th>Totaal per week</th>
        <th>{{ number_format(array_sum($hours), 1, ',', '') }} u</th>
      </tr>
    </tfoot>
  </table>
  <button type="submit">Opslaan</button>
</form>
@endsection
