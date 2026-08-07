<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('nusahr:process-absences')->weekdays()->dailyAt('23:30')->withoutOverlapping();
Schedule::command('nusahr:publish-announcements')->everyFiveMinutes()->withoutOverlapping();
