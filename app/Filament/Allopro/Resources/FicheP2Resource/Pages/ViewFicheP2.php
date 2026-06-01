<?php

namespace App\Filament\Allopro\Resources\FicheP2Resource\Pages;

use App\Filament\Allopro\Resources\FicheP2Resource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFicheP2 extends ViewRecord
{
    protected static string $resource = FicheP2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
