<?php

use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->command('auth:clear-resets')->everyTwoHours();
};

