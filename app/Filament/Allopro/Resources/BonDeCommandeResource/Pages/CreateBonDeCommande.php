<?php
namespace App\Filament\Allopro\Resources\BonDeCommandeResource\Pages;
use App\Filament\Allopro\Resources\BonDeCommandeResource;
use Filament\Resources\Pages\CreateRecord;


class CreateBonDeCommande extends CreateRecord
{
    protected static string $resource = BonDeCommandeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['numero'] = \App\Models\BonDeCommande::genererNumero();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'BC ' . $this->getRecord()->numero . ' créé';
    }
}
