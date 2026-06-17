<?php

namespace App\Providers;

use App\Services\AircallService;
use App\Models\CrmSetting;
use App\Services\Crm\CrmSettingsService;
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
        $this->app->singleton(CrmSettingsService::class);

        // Telescope reste un paquet dev (composer require-dev) : on ne
        // l'enregistre qu'en local pour ne jamais casser
        // `composer install --no-dev` en production/CI.
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
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

        CrmSetting::saved(fn () => app(CrmSettingsService::class)->forget());
        CrmSetting::deleted(fn () => app(CrmSettingsService::class)->forget());
    }
}
