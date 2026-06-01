<?php

namespace App\Filament\Allopro\Resources\FicheP2Resource\Pages;

use App\Filament\Allopro\Resources\FicheP2Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFicheP2 extends EditRecord
{
    protected static string $resource = FicheP2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
