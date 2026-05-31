<?php

namespace App\Providers;

use App\Services\AircallService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AircallService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('siret', function ($attribute, $value) {
            return preg_match('/^\d{14}$/', $value);
        }, 'Le SIRET doit contenir exactement 14 chiffres.');

        // Validation description P2 >= 30 chars
        Validator::extend('description_p2', function ($attribute, $value) {
            return strlen($value) >= 30;
        }, 'La description doit contenir au minimum 30 caractères.');
    }
}
