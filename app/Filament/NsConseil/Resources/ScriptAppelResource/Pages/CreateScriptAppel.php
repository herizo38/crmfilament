<?php
namespace App\Filament\NsConseil\Resources\ScriptAppelResource\Pages;

use App\Filament\NsConseil\Resources\ScriptAppelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScriptAppel extends CreateRecord
{
    protected static string $resource = ScriptAppelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
