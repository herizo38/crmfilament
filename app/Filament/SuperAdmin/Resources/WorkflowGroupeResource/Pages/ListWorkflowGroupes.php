<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages;

use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowGroupes extends ListRecords
{
    protected static string $resource = WorkflowGroupeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
