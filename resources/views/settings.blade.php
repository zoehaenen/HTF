@extends('layouts.app')

@section('content')
<h1>Instellingen</h1>
<p class="small-muted">Beschikbare uren per weekdag. Deze verdeling bepaalt hoe taken over de dagen worden gepland.</p>

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
        <td>{{ $dag }}</td>
        <td><input type="number" name="hours[{{ $w }}]" value="{{ old('hours.' . $w, number_format($hours[$w] ?? 2, 1)) }}" step="0.5" min="0" max="24" required></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <button type="submit">Opslaan</button>
</form>
@endsection
