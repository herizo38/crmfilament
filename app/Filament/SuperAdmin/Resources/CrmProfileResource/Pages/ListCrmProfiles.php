<?php

namespace App\Filament\SuperAdmin\Resources\CrmProfileResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrmProfiles extends ListRecords
{
    protected static string $resource = CrmProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
