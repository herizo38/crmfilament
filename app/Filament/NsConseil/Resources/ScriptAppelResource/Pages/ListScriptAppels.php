<?php
namespace App\Filament\NsConseil\Resources\ScriptAppelResource\Pages;

use App\Filament\NsConseil\Resources\ScriptAppelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScriptAppels extends ListRecords
{
    protected static string $resource = ScriptAppelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
