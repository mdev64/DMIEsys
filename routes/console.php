<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('cache:prune-stale-tags')->hourly();
Schedule::command('AllocateMaintenanceTasks')->onOneServer()->environments(['production'])->dailyAt('00:30');
Schedule::command('RemindAdvisorsToLogComments')->onOneServer()->environments(['production'])->weeklyOn(2, '8:00');
Schedule::command('RemindFacilityBooking')->onOneServer()->everyThirtyMinutes()->environments(['production']);
