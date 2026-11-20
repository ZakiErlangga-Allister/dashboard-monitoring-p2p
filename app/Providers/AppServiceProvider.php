<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\LogAktivitas;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        event::listen(Login::class, function (Login $event) {
            LogAktivitas::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'aksi' => 'login',
                'rincian' => 'Login berhasil',
            ]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            LogAktivitas::create([
                'user_id' => null,
                'aksi' => 'login_gagal',
                'rincian' => 'Login gagal untuk email '.mb_substr((string) ($event->credentials['email'] ?? '-'), 0, 100),
            ]);
        });
    }
}
