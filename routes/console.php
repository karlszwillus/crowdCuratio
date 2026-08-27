<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

Artisan::command(
    'inspire',
    function () {
        $this->comment(Inspiring::quote());
    }
)->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Die tatsaechliche Ausfuehrung braucht den Laravel-Scheduler-Tick
| einmal pro Minute — via System-Crontab:
|     * * * * * cd /pfad && php artisan schedule:run >> /dev/null 2>&1
| Ohne diesen Cron laufen die Tasks unten nicht.
|
*/

// B2 · DSGVO-Konto-Loeschung: taeglich abgelaufene Konten soft-loeschen.
// `withoutOverlapping` verhindert Doppel-Laeufe bei langem Job. Failures
// wandern in den Standard-Log-Channel — der Command selbst loggt
// bereits pro Konto strukturiert (account.deletion.purged).
Schedule::command('users:purge-scheduled')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('schedule.users_purge_scheduled.success');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('schedule.users_purge_scheduled.failure');
    });
