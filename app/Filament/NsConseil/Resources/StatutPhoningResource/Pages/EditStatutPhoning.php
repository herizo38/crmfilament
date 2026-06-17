<?php

namespace App\Filament\NsConseil\Resources\StatutPhoningResource\Pages;

use App\Filament\NsConseil\Resources\StatutPhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatutPhoning extends EditRecord
{
    protected static string $resource = StatutPhoningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
