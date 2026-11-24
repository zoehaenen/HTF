<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanningService
{
    private const EPS = 0.01;

    public function plan(Collection $tasks, array $available): array
    {
        $today = Carbon::today();
        $used = [];
        $allocations = [];
        $warnings = [];

        $open = $tasks->reject(fn (Task $t) => $t->done)
            ->sortBy(fn (Task $t) => $t->deadline->format('Y-m-d') . sprintf('%010d', $t->id))
            ->values();

        foreach ($open as $task) {
            if ($task->deadline->lte($today)) {
                $warnings[$task->id] = $task->deadline->lt($today) ? 'Deadline verstreken' : 'Te weinig tijd';
                continue;
            }

            $capacity = [];
            for ($d = $today->copy(); $d->lt($task->deadline); $d->addDay()) {
                $capacity[$d->toDateString()] = $available[$d->dayOfWeekIso - 1] ?? 0;
            }

            $remaining = (float) $task->estimated_hours;
            $taskHours = [];

            while ($remaining > self::EPS) {
                $free = [];
                foreach ($capacity as $date => $cap) {
                    $left = $cap - ($used[$date] ?? 0);
                    if ($left > self::EPS) {
                        $free[$date] = $left;
                    }
                }
                $weightTotal = 0;
                foreach ($free as $date => $left) {
                    $weightTotal += $capacity[$date];
                }
                if ($weightTotal <= 0) {
                    break;
                }

                $round = $remaining;
                foreach ($free as $date => $left) {
                    $give = min($left, $round * $capacity[$date] / $weightTotal);
                    $used[$date] = ($used[$date] ?? 0) + $give;
                    $taskHours[$date] = ($taskHours[$date] ?? 0) + $give;
                    $remaining -= $give;
                }
            }

            if ($remaining > self::EPS) {
                $warnings[$task->id] = 'Te weinig tijd';
            }

            foreach ($taskHours as $date => $hours) {
                if ($hours > self::EPS) {
                    $allocations[$date][] = ['task' => $task, 'hours' => $hours];
                }
            }
        }

        $planning = [];
        for ($i = 0; $i < 14; $i++) {
            $d = $today->copy()->addDays($i);
            $items = $allocations[$d->toDateString()] ?? [];
            $planning[] = [
                'date' => $d,
                'items' => $items,
                'total' => array_sum(array_column($items, 'hours')),
                'available' => $available[$d->dayOfWeekIso - 1] ?? 0,
            ];
        }

        $weeks = [];
        if ($allocations) {
            $last = Carbon::parse(max(array_keys($allocations)))->startOfWeek();
            for ($w = $today->copy()->startOfWeek(); $w->lte($last); $w->addWeek()) {
                $weeks[$w->toDateString()] = 0;
            }
            foreach ($allocations as $date => $items) {
                $weeks[Carbon::parse($date)->startOfWeek()->toDateString()] += array_sum(array_column($items, 'hours'));
            }
        }

        return ['planning' => $planning, 'warnings' => $warnings, 'weeks' => $weeks];
    }
}
