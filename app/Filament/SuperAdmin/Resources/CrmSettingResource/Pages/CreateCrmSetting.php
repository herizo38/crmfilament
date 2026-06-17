<?php

namespace App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmSettingResource;
use App\Services\Crm\CrmSettingsService;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmSetting extends CreateRecord
{
    protected static string $resource = CrmSettingResource::class;

    protected function afterCreate(): void
    {
        app(CrmSettingsService::class)->forget();
    }
}
