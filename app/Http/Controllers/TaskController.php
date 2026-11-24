<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Task;
use App\Services\AnthropicService;
use App\Services\PlanningService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskController extends Controller
{
    public function create()
    {
        return view('add');
    }

    public function analyse(Request $request, AnthropicService $api)
    {
        $request->validate(['text' => 'required|string']);

        $result = $api->extractTasks($request->input('text'));

        if (isset($result['error'])) {
            return back()->withInput()->with(['error' => $result['error'], 'raw' => $result['raw']]);
        }

        session(['draft_tasks' => $result['tasks'], 'source_text' => $request->input('text')]);

        return redirect()->route('confirm');
    }

    public function confirm()
    {
        $draft = session('draft_tasks');
        if (!$draft) {
            return redirect()->route('create');
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
        $source = session('source_text');
        $saved = 0;

        foreach ($draft as $i => $t) {
            if (empty($rows[$i]['save'])) {
                continue;
            }
            $row = $rows[$i] ?? [];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['deadline'] ?? '')) {
                continue;
            }
            Task::create([
                'title' => trim($row['title'] ?? '') !== '' ? trim($row['title']) : 'Zonder titel',
                'course' => trim($row['course'] ?? '') !== '' ? trim($row['course']) : null,
                'deadline' => $row['deadline'],
                'estimated_hours' => is_numeric($row['estimated_hours'] ?? null) ? max(0.5, (float) $row['estimated_hours']) : 2.0,
                'done' => false,
                'source_text' => $source,
            ]);
            $saved++;
        }

        session()->forget('draft_tasks');

        return redirect()->route('overview')->with('status', $saved . ' taak/taken opgeslagen.');
    }

    public function overview(PlanningService $planner)
    {
        $tasks = Task::orderBy('deadline')->orderBy('id')->get();

        $hours = Setting::pluck('hours', 'weekday')->all();
        $available = [];
        foreach (range(0, 6) as $w) {
            $available[$w] = (float) ($hours[$w] ?? 2);
        }

        $plan = $planner->plan($tasks, $available);

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
        return redirect()->route('overview');
    }

    public function settings()
    {
        $hours = Setting::pluck('hours', 'weekday')->all();
        return view('settings', ['hours' => $hours]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'hours' => 'required|array|size:7',
            'hours.*' => 'nullable|numeric|min:0|max:24',
        ]);

        foreach (range(0, 6) as $w) {
            Setting::updateOrCreate(
                ['weekday' => $w],
                ['hours' => max(0, (float) ($data['hours'][$w] ?? 2))]
            );
        }

        return redirect()->route('settings')->with('status', 'Instellingen opgeslagen.');
    }
}
