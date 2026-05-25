<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archivage automatique des patients inactifs — chaque nuit à 01h00
Schedule::command('patients:auto-archive')->dailyAt('01:00');
