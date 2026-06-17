<?php

namespace App\Filament\NsConseil\Resources\StatutPhoningResource\Pages;

use App\Filament\NsConseil\Resources\StatutPhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatutPhonings extends ListRecords
{
    protected static string $resource = StatutPhoningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
