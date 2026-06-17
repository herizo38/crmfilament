<?php

namespace App\Filament\NsConseil\Pages;

use Filament\Pages\Page;

class WorkflowProspectionCse extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Workflow prospection CSE';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Logigramme de prospection CSE v2';

    protected static string $view = 'filament.ns-conseil.pages.workflow-prospection-cse';

    protected static ?string $slug = 'workflow-prospection-cse';
}
