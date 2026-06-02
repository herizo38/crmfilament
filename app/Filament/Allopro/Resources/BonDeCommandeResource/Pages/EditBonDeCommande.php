<?php
namespace App\Filament\Allopro\Resources\BonDeCommandeResource\Pages;

use App\Filament\Allopro\Resources\BonDeCommandeResource;
use Filament\Actions;

use Filament\Resources\Pages\EditRecord;


class EditBonDeCommande extends EditRecord
{
    protected static string $resource = BonDeCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
        ];
    }
}
