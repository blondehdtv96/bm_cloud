<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup otomatis sesuai jadwal (lihat prompt/11_Backup.md)
Schedule::command('backup:run daily')->daily()->at('01:00');
Schedule::command('backup:run weekly')->weekly()->sundays()->at('02:00');
Schedule::command('backup:run monthly')->monthly()->at('03:00');
