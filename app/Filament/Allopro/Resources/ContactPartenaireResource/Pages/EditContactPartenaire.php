<?php

namespace App\Filament\Allopro\Resources\ContactPartenaireResource\Pages;

use App\Filament\Allopro\Resources\ContactPartenaireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactPartenaire extends EditRecord
{
    protected static string $resource = ContactPartenaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
