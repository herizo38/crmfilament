<?php

namespace App\Filament\Allopro\Resources\FicheP2Resource\Pages;

use App\Filament\Allopro\Resources\FicheP2Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFicheP2s extends ListRecords
{
    protected static string $resource = FicheP2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
