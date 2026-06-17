<?php

namespace App\Filament\NsConseil\Pages;

use Filament\Pages\Page;

class StatutsAppelsCse extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Statuts appels CSE';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Référentiel statuts d\'appels CSE v2';

    protected static string $view = 'filament.ns-conseil.pages.statuts-appels-cse';

    protected static ?string $slug = 'statuts-appels-cse';
}
