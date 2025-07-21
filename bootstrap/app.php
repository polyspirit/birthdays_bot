<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Send birthday notifications at 11:00 AM GMT+4 (07:00 UTC)
        $schedule->command('birthday:send-notifications')
            ->dailyAt('07:00')
            ->timezone('UTC')
            ->appendOutputTo(storage_path('logs/cron.log'));

        // Send birthday notifications at 8:00 PM GMT+4 (16:00 UTC)
        $schedule->command('birthday:send-notifications')
            ->dailyAt('16:00')
            ->timezone('UTC')
            ->appendOutputTo(storage_path('logs/cron.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
