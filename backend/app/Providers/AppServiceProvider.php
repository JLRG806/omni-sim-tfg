<?php

namespace App\Providers;

use App\Services\OrquestadorIAInterface;
use App\Services\OrquestadorIAService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // CU-28/29: el backend habla con n8n, nunca con Ollama directamente.
        // En dev n8n puede no estar disponible → OrquestadorIAService usa mock.
        $this->app->bind(OrquestadorIAInterface::class, OrquestadorIAService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CU-03: el enlace de reset apunta a la SPA Vue, no a una ruta Laravel.
        // La vista RecuperarCuentaView lee token y email desde los query params.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = config('app.frontend_url', 'http://localhost:8081');
            return $frontend
                . '/recuperar-cuenta'
                . '?token=' . $token
                . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
