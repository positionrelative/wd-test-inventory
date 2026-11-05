<?php

use App\Models\ApiRequestLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Removes API traffic log entries older than api_logging.retention_days.
Schedule::command('model:prune', ['--model' => [ApiRequestLog::class]])->daily();
