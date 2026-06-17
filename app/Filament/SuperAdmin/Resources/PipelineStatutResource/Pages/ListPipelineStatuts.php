<?php

namespace App\Filament\SuperAdmin\Resources\PipelineStatutResource\Pages;

use App\Filament\SuperAdmin\Resources\PipelineStatutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPipelineStatuts extends ListRecords
{
    protected static string $resource = PipelineStatutResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
