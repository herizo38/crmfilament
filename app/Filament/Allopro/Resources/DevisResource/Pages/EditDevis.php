<?php
namespace App\Filament\Allopro\Resources\DevisResource\Pages;

use App\Filament\Allopro\Resources\DevisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDevis extends EditRecord
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Devis mis à jour';
    }
}
