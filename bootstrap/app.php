<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserHasRole; 

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 2. DAFTARKAN ALIAS DI SINI
        $middleware->alias([
            'role' => EnsureUserHasRole::class, // <--- TAMBAHKAN INI
        ]);


        // (Kode lama Anda tetap biarkan di bawahnya)
        // PENTING: Matikan CSRF untuk endpoint callback Midtrans
        $middleware->validateCsrfTokens(except: [
            'midtrans/callback', 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();