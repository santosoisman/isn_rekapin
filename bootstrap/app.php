<?php

use App\Http\Middleware\EnsureCurrentBook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'current.book' => EnsureCurrentBook::class,
        ]);

        // Belum login lalu buka halaman yang dilindungi => arahkan ke landing (app ini tidak punya route 'login')
        $middleware->redirectGuestsTo(fn () => route('landing'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();