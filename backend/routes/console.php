<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Domain\Profiles\Jobs\NotifyOwnersOfPendingSubmissions;
use App\Domain\Reminders\Jobs\ScheduleRemindersForAllUsers;
use App\Domain\Reminders\Jobs\SendDueNotifications;
use App\Domain\Reminders\Jobs\SendWeeklyDigests;
use Illuminate\Support\Facades\Schedule;

// Planificarea merge o dată pe zi, pe un orizont de câteva săptămâni:
// o rulare ratată e recuperată de următoarea.
Schedule::job(new ScheduleRemindersForAllUsers)->dailyAt('02:00');

// Trimiterea rulează des, ca ora preferată a fiecărui utilizator să fie
// respectată indiferent de fusul orar.
Schedule::job(new SendDueNotifications)->everyFiveMinutes()->withoutOverlapping();

// Din oră în oră: fiecare utilizator îl primește luni la 9 dimineața, în
// fusul lui. O rulare zilnică ar trimite emailuri la miezul nopții.
Schedule::job(new SendWeeklyDigests)->hourly()->withoutOverlapping();

// „Cine este?”: o singură notificare pentru completările care așteaptă, în
// afara orelor de liniște și cel mult una pe zi (S9.8).
Schedule::job(new NotifyOwnersOfPendingSubmissions)->everyFiveMinutes()->withoutOverlapping();
