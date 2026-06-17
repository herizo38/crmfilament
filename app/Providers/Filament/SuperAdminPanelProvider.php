<?php

namespace App\Providers\Filament;

use App\Filament\SuperAdmin\Pages\Dashboard;
use App\Filament\SuperAdmin\Pages\DatabaseManager;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->login()
            ->brandName('⚙️ Super Administration')
            ->brandLogo(null)
            ->colors([
                'primary' => Color::Violet,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Cyan,
                'gray'    => Color::Zinc,
            ])
            ->navigationGroups([
                NavigationGroup::make('Utilisateurs & Accès')
                    ->icon('heroicon-o-shield-check'),
                NavigationGroup::make('Paramétrage CRM')
                    ->icon('heroicon-o-adjustments-horizontal'),
                NavigationGroup::make('Base de données')
                    ->icon('heroicon-o-circle-stack'),
                NavigationGroup::make('Système')
                    ->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('Logs & Audit')
                    ->icon('heroicon-o-document-magnifying-glass'),
            ])
            ->discoverResources(
                in: app_path('Filament/SuperAdmin/Resources'),
                for: 'App\\Filament\\SuperAdmin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/SuperAdmin/Pages'),
                for: 'App\\Filament\\SuperAdmin\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/SuperAdmin/Widgets'),
                for: 'App\\Filament\\SuperAdmin\\Widgets'
            )
            ->pages([
                Dashboard::class,
                DatabaseManager::class,
            ])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureSuperAdmin::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa();
    }
}
