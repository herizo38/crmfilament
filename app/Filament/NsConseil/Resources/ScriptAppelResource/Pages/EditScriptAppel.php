<?php
namespace App\Filament\NsConseil\Resources\ScriptAppelResource\Pages;

use App\Filament\NsConseil\Resources\ScriptAppelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScriptAppel extends EditRecord
{
    protected static string $resource = ScriptAppelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
