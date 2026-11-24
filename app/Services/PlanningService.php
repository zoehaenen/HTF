<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanningService
{
    public function plan(Collection $tasks, array $available): array
    {
        $used = [];
        $allocations = [];
        $warnings = [];
        $today = Carbon::today();

        $open = $tasks->filter(fn (Task $t) => !$t->done)
            ->sortBy(fn (Task $t) => $t->deadline->format('Y-m-d') . '-' . $t->id)
            ->values();

        foreach ($open as $task) {
            $end = $task->deadline->copy()->subDay();
            if ($end->lt($today)) {
                $warnings[$task->id] = true;
                continue;
            }

            $days = [];
            for ($d = $today->copy(); $d->lte($end); $d->addDay()) {
                $days[] = $d->copy();
            }

            $weights = [];
            foreach ($days as $d) {
                $weights[] = max(0.0, (float) ($available[$d->dayOfWeekIso - 1] ?? 0));
            }
            $totalWeight = array_sum($weights);
            if ($totalWeight <= 0) {
                $weights = array_fill(0, count($days), 1);
                $totalWeight = count($days);
            }

            $remaining = (float) $task->estimated_hours;
            $taskHours = [];

            foreach ($days as $i => $d) {
                if ($remaining <= 0.005) {
                    break;
                }
                $date = $d->toDateString();
                $free = max(0.0, (float) ($available[$d->dayOfWeekIso - 1] ?? 0) - ($used[$date] ?? 0));
                $give = min($remaining, $remaining * $weights[$i] / $totalWeight, $free);
                if ($give > 0) {
                    $used[$date] = ($used[$date] ?? 0) + $give;
                    $remaining -= $give;
                    $taskHours[$date] = ($taskHours[$date] ?? 0) + $give;
                }
            }

            $guard = 0;
            while ($remaining > 0.005 && $guard++ < 30) {
                $freeTotal = 0;
                $freeDays = [];
                foreach ($days as $d) {
                    $date = $d->toDateString();
                    $free = max(0.0, (float) ($available[$d->dayOfWeekIso - 1] ?? 0) - ($used[$date] ?? 0));
                    if ($free > 0.005) {
                        $freeDays[$date] = $free;
                        $freeTotal += $free;
                    }
                }
                if ($freeTotal <= 0) {
                    break;
                }
                foreach ($freeDays as $date => $free) {
                    $give = min($free, $remaining * $free / $freeTotal);
                    if ($give <= 0) {
                        continue;
                    }
                    $used[$date] = ($used[$date] ?? 0) + $give;
                    $remaining -= $give;
                    $taskHours[$date] = ($taskHours[$date] ?? 0) + $give;
                }
            }

            if ($remaining > 0.005) {
                $warnings[$task->id] = true;
            }

            foreach ($taskHours as $date => $hours) {
                $allocations[$date][] = ['task' => $task, 'hours' => $hours];
            }
        }

        $planning = [];
        for ($d = $today->copy(); $d->lt($today->copy()->addDays(14)); $d->addDay()) {
            $date = $d->toDateString();
            $items = $allocations[$date] ?? [];
            $planning[] = [
                'date' => $d->copy(),
                'items' => $items,
                'total' => array_sum(array_column($items, 'hours')),
            ];
        }

        $weeks = [];
        foreach ($allocations as $date => $items) {
            $week = Carbon::parse($date)->startOfWeek()->toDateString();
            $weeks[$week] = ($weeks[$week] ?? 0) + array_sum(array_column($items, 'hours'));
        }
        ksort($weeks);

        return ['planning' => $planning, 'warnings' => $warnings, 'weeks' => $weeks];
    }
}
