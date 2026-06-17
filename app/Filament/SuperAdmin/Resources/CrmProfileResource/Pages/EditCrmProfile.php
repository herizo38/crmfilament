<?php

namespace App\Filament\SuperAdmin\Resources\CrmProfileResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrmProfile extends EditRecord
{
    protected static string $resource = CrmProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->record->is_system),
        ];
    }
}
