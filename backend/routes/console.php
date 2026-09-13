<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Domain\Catalog\Jobs\AggregateOldClicks;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Jobs\NotifyOwnersOfPendingSubmissions;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Reminders\Jobs\ScheduleRemindersForAllUsers;
use App\Domain\Reminders\Jobs\SendDueNotifications;
use App\Domain\Reminders\Jobs\SendWeeklyDigests;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Models\PersonalAccessToken;
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

// Joburile eșuate păstrează excepția, care poate conține date din cerere: o
// săptămână ajunge pentru depanare (docs/21, M-11).
Schedule::command('queue:prune-failed --hours=168')->daily();

// Retenția din docs/21, noaptea, după planificarea de la 02:00. Se șterg
// istoricul reminderelor mai vechi de 13 luni și sesiunile din aplicație
// nefolosite de 12 luni (M-11), persoanele șterse de peste 30 de zile (M-07)
// și completările din link rămase fără răspuns 30 de zile (M-10). Persoanele
// vin înaintea completărilor, ca o completare rămasă fără persoană să plece
// în aceeași noapte.
Schedule::command('model:prune', ['--model' => [
    QueuedNotification::class,
    PersonalAccessToken::class,
    Person::class,
    ProfileSubmission::class,
]])->dailyAt('03:00');

// Clickurile mai vechi de 24 de luni rămân doar ca total pe lună, comerciant
// și ofertă (M-08).
Schedule::job(new AggregateOldClicks)->dailyAt('03:30');
