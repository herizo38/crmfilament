<?php

namespace Database\Seeders;

use App\Models\CrmSetting;
use App\Services\Crm\CrmSettingsService;
use Illuminate\Database\Seeder;

class CrmSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = require database_path('seeders/data/crm_settings.php');

        foreach ($settings as $setting) {
            CrmSetting::updateOrCreate(
                ['groupe' => $setting['groupe'], 'cle' => $setting['cle']],
                $setting
            );
        }

        app(CrmSettingsService::class)->forget();
    }
}
