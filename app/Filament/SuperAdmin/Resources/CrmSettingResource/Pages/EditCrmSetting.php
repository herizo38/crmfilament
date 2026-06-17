<?php

namespace App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmSettingResource;
use App\Services\Crm\CrmSettingsService;
use Filament\Resources\Pages\EditRecord;

class EditCrmSetting extends EditRecord
{
    protected static string $resource = CrmSettingResource::class;

    protected function afterSave(): void
    {
        app(CrmSettingsService::class)->forget();
    }
}
