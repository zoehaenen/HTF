<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Task;
use App\Services\AnthropicService;
use App\Services\PlanningService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function create()
    {
        return view('add');
    }

    public function analyse(Request $request, AnthropicService $api)
    {
        $request->validate(['text' => 'required|string|max:20000'], [
            'text.required' => 'Plak eerst een tekst.',
            'text.max' => 'De tekst is te lang (maximaal 20000 tekens).',
        ]);

        $result = $api->extractTasks($request->input('text'));

        if (isset($result['error'])) {
            return back()->withInput()->with(['error' => $result['error'], 'raw' => $result['raw']]);
        }

        if (empty($result['tasks'])) {
            return back()->withInput()->with('error', 'Er werden geen taken gevonden in deze tekst.');
        }

        session(['draft_tasks' => $result['tasks'], 'source_text' => $request->input('text')]);

        return redirect()->route('confirm');
    }

    public function confirm()
    {
        $draft = session('draft_tasks');
        if (!$draft) {
            return redirect()->route('create')->with('status', 'Er zijn geen taken om te bevestigen. Analyseer eerst een tekst.');
        }

        return view('confirm', ['draft' => $draft]);
    }

    public function store(Request $request)
    {
        $draft = session('draft_tasks');
        if (!$draft) {
            return redirect()->route('create');
        }

        $rows = $request->input('tasks', []);
        $errors = [];
        $valid = [];

        foreach (array_keys($draft) as $i) {
            $row = $rows[$i] ?? [];
            if (empty($row['save'])) {
                continue;
            }
            $deadline = AnthropicService::validDate($row['deadline'] ?? null);
            if (!$deadline) {
                $errors["tasks.$i.deadline"] = 'Vul een geldige deadline in.';
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $errors["tasks.$i.title"] = 'Vul een titel in.';
                continue;
            }
            $course = trim((string) ($row['course'] ?? ''));
            $hours = $row['estimated_hours'] ?? null;
            $valid[] = [
                'title' => mb_substr($title, 0, 255),
                'course' => $course !== '' ? mb_substr($course, 0, 255) : null,
                'deadline' => $deadline,
                'estimated_hours' => is_numeric($hours) ? min(500, max(0.5, (float) $hours)) : 2.0,
            ];
        }

        if ($errors) {
            return redirect()->route('confirm')->withInput()->withErrors($errors);
        }

        $source = session('source_text');
        foreach ($valid as $data) {
            Task::create($data + ['done' => false, 'source_text' => $source]);
        }

        session()->forget(['draft_tasks', 'source_text']);

        $count = count($valid);

        return redirect()->route('overview')->with('status', $count === 1 ? '1 taak opgeslagen.' : $count . ' taken opgeslagen.');
    }

    public function overview(PlanningService $planner)
    {
        $tasks = Task::orderBy('done')->orderBy('deadline')->orderBy('id')->get();
        $plan = $planner->plan($tasks, Setting::available());

        return view('overview', [
            'tasks' => $tasks,
            'planning' => $plan['planning'],
            'warnings' => $plan['warnings'],
            'weeks' => $plan['weeks'],
        ]);
    }

    public function toggleDone(Task $task)
    {
        $task->update(['done' => !$task->done]);

        return redirect()->route('overview');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('overview')->with('status', 'Taak verwijderd.');
    }

    public function settings()
    {
        return view('settings', ['hours' => Setting::available()]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'hours' => 'required|array|size:7',
            'hours.*' => 'required|numeric|min:0|max:24',
        ], [
            'hours.*.required' => 'Vul voor elke dag een aantal uren in.',
            'hours.*.numeric' => 'Uren moeten een getal zijn.',
            'hours.*.min' => 'Uren kunnen niet negatief zijn.',
            'hours.*.max' => 'Een dag heeft maximaal 24 uur.',
        ]);

        foreach (range(0, 6) as $w) {
            Setting::updateOrCreate(['weekday' => $w], ['hours' => (float) $data['hours'][$w]]);
        }

        return redirect()->route('settings')->with('status', 'Instellingen opgeslagen.');
    }
}
