@extends('layouts.app')

@section('title', 'Overzicht')

@section('content')
<h1>Overzicht</h1>

<h2>Taken</h2>
@if ($tasks->isEmpty())
<p>Nog geen taken. <a href="{{ route('create') }}">Voeg er een toe.</a></p>
@else
<div class="overflow-auto">
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
        @if (!$task->done && isset($warnings[$task->id]))
        <br><span class="warning">{{ $warnings[$task->id] }}</span>
        @endif
      </td>
      <td>{{ $task->course ?? '-' }}</td>
      <td class="nowrap">
        {{ $task->deadline->isoFormat('dd D MMM Y') }}
        @unless ($task->done)
        @php $days = (int) round(\Illuminate\Support\Carbon::today()->diffInDays($task->deadline, false)); @endphp
        <br><small class="muted">{{ match (true) { $days === 0 => 'vandaag', $days === 1 => 'morgen', $days > 1 => "over $days dagen", $days === -1 => '1 dag te laat', default => abs($days) . ' dagen te laat' } }}</small>
        @endunless
      </td>
      <td>{{ number_format($task->estimated_hours, 1, ',', '') }}</td>
      <td class="nowrap">
        <form method="post" action="{{ route('tasks.done', $task) }}" class="inline">
          @csrf
          <button type="submit" class="outline small">{{ $task->done ? 'Heropen' : 'Klaar' }}</button>
        </form>
        <form method="post" action="{{ route('tasks.destroy', $task) }}" class="inline" onsubmit="return confirm('Deze taak verwijderen?')">
          @csrf
          <button type="submit" class="outline secondary small">Verwijderen</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
</div>
@endif

<h2>Planning komende 14 dagen</h2>
<div class="overflow-auto">
<table>
  <thead>
    <tr>
      <th>Dag</th>
      <th>Taken</th>
      <th>Totaal</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($planning as $day)
    <tr @if ($day['date']->isToday()) class="today" @endif>
      <td class="nowrap">{{ $day['date']->isoFormat('dd D MMM') }}</td>
      <td>
        @forelse ($day['items'] as $item)
        {{ $item['task']->title }}: {{ number_format($item['hours'], 1, ',', '') }} u<br>
        @empty
        <span class="muted">vrij</span>
        @endforelse
      </td>
      <td class="nowrap">{{ number_format($day['total'], 1, ',', '') }} / {{ number_format($day['available'], 1, ',', '') }} u</td>
    </tr>
    @endforeach
  </tbody>
</table>
</div>

<h2>Werkdruk per week</h2>
@if (empty($weeks))
<p class="muted">Nog geen geplande uren.</p>
@else
<canvas id="chart" height="110"
  data-labels="{{ json_encode(array_map(fn ($w) => 'Week van ' . \Illuminate\Support\Carbon::parse($w)->isoFormat('D MMM'), array_keys($weeks))) }}"
  data-values="{{ json_encode(array_map(fn ($v) => round($v, 1), array_values($weeks))) }}"></canvas>
@endif
@endsection

@if (!empty($weeks))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const chartEl = document.getElementById('chart');
new Chart(chartEl, {
    type: 'bar',
    data: {
        labels: JSON.parse(chartEl.dataset.labels),
        datasets: [{
            label: 'Geplande uren',
            data: JSON.parse(chartEl.dataset.values),
            backgroundColor: '#1095c1'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Uren' } } }
    }
});
</script>
@endpush
@endif
