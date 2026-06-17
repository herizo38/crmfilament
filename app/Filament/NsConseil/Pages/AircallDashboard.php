<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Filament\NsConseil\Widgets\AircallAppelsRecents;
use App\Filament\NsConseil\Widgets\AircallStatsOverview;
use App\Services\AircallService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AircallDashboard extends Page
{
    use HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Dashboard Aircall';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

    protected static ?string $title = 'Dashboard Aircall';

    protected static string $view = 'filament.ns-conseil.pages.aircall-dashboard';

    public bool $connexionOk = false;

    public function mount(): void
    {
        $this->connexionOk = app(AircallService::class)->testConnection();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    \Cache::flush();
                    \Artisan::call('aircall:sync', [
                        '--pages' => 3,
                        '--per-page' => 50,
                        '--from' => now()->subDay()->timestamp,
                    ]);
                    Notification::make()
                        ->title('Synchronisation terminée')
                        ->body(\Artisan::output())
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [AircallStatsOverview::class];
    }

    protected function getFooterWidgets(): array
    {
        return [AircallAppelsRecents::class];
    }
}
