<?php

namespace App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrmSettings extends ListRecords
{
    protected static string $resource = CrmSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
