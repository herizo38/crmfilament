<?php
namespace App\Filament\Allopro\Resources\FactureResource\Pages;


use App\Filament\Allopro\Resources\FactureResource;

use Filament\Resources\Pages\CreateRecord;


class CreateFacture extends CreateRecord
{
    protected static string $resource = FactureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['numero'] = \App\Models\Facture::genererNumero();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Facture ' . $this->getRecord()->numero . ' créée';
    }
}
