<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WorkingDayCalculator
{
    public function between(CarbonInterface $start, CarbonInterface $end): float
    {
        $holidays = Holiday::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->all();
        $days = 0;
        for ($date = CarbonImmutable::instance($start)->startOfDay(); $date->lte($end); $date = $date->addDay()) {
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidays, true)) {
                $days++;
            }
        }

        return (float) $days;
    }
}
