@extends('layouts.app')

@section('content')
<h1>Overzicht</h1>

<h2>Taken</h2>
@if ($tasks->isEmpty())
<p>Geen taken. <a href="{{ route('create') }}">Voeg er een toe.</a></p>
@else
<table>
  <thead>
    <tr>
      <th>Titel</th>
      <th>Vak</th>
      <th>Deadline</th>
      <th>Uren</th>
      <th>Acties</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($tasks as $task)
    <tr class="{{ $task->done ? 'done-row' : '' }}">
      <td>
        {{ $task->title }}
        @if (!$task->done && ($warnings[$task->id] ?? false))
        <span class="invalid-date small-muted">Te weinig tijd</span>
        @endif
      </td>
      <td>{{ $task->course ?? '-' }}</td>
      <td>{{ $task->deadline->isoFormat('ddd D MMM Y') }}</td>
      <td>{{ number_format($task->estimated_hours, 1) }}</td>
      <td>
        <form method="post" action="{{ route('tasks.done', $task) }}" style="display:inline">
          @csrf
          <button type="submit" class="outline">{{ $task->done ? 'Heropen' : 'Klaar' }}</button>
        </form>
        <form method="post" action="{{ route('tasks.destroy', $task) }}" style="display:inline">
          @csrf
          <button type="submit" class="outline">Verwijderen</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

<h2>Planning komende 14 dagen</h2>
<table>
  <thead>
    <tr>
      <th>Dag</th>
      <th>Taken en uren</th>
      <th>Totaal</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($planning as $day)
    <tr>
      <td>{{ $day['date']->isoFormat('ddd D MMM') }}</td>
      <td>
        @forelse ($day['items'] as $item)
        {{ $item['task']->title }}: {{ number_format($item['hours'], 1) }} u<br>
        @empty
        <span class="small-muted">vrij</span>
        @endforelse
      </td>
      <td>{{ number_format($day['total'], 1) }} u</td>
    </tr>
    @endforeach
  </tbody>
</table>

<h2>Werkdruk per week</h2>
<canvas id="chart" height="120"></canvas>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: @json(array_map(fn ($w) => 'Week van ' . \Illuminate\Support\Carbon::parse($w)->isoFormat('D MMM'), array_keys($weeks))),
        datasets: [{
            label: 'Geplande uren',
            data: @json(array_map(fn ($v) => round($v, 2), array_values($weeks))),
            backgroundColor: '#4b0082'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>
@endpush
