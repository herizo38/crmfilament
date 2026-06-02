<?php
namespace App\Filament\Allopro\Resources\DevisResource\Pages;

use App\Filament\Allopro\Resources\DevisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDevis extends CreateRecord
{
    protected static string $resource = DevisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['numero'] = \App\Models\Devis::genererNumero();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Devis ' . $this->getRecord()->numero . ' créé';
    }
}
