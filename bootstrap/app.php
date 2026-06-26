<?php
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\LanguageTranslator::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
            'check.route.permission' => \App\Http\Middleware\CheckRoutePermission::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('find_driver:for_regular_ride')->everyMinute();
        $schedule->command('scheduleride:process-schedule-rides')->everyMinute();
        $schedule->command('scheduleride:send-notifications')->everyFifteenMinutes();
        $schedule->command('scheduleride:cancel-overdue-rides')->everyFiveMinutes();
        $schedule->command('rides:auto-cancel-arrived')->everyFiveMinutes();
        $schedule->command('drivers:mark-inactive-offline')->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api*')) {
                return response()->json(['error' => 'unauthenticated', 'message' => __('auth.unauthenticated')], 401);
            }
        });
    })
    ->create();
