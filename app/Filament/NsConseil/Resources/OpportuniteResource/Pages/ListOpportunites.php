<?php

namespace App\Filament\NsConseil\Resources\OpportuniteResource\Pages;

use App\Filament\NsConseil\Resources\OpportuniteResource;
use App\Models\Opportunite;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOpportunites extends ListRecords
{
    protected static string $resource = OpportuniteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle opportunité'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Opportunite::count()),

            'actives' => Tab::make('Actives')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotIn('statut', ['converti', 'perdu']))
                ->badge(Opportunite::whereNotIn('statut', ['converti', 'perdu'])->count())
                ->badgeColor('warning'),

            'nouveau' => Tab::make('Nouvelles')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', 'nouveau'))
                ->badge(Opportunite::where('statut', 'nouveau')->count())
                ->badgeColor('info'),

            'en_negociation' => Tab::make('En négociation')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', 'en_negociation'))
                ->badge(Opportunite::where('statut', 'en_negociation')->count())
                ->badgeColor('purple'),

            'qualifiee' => Tab::make('Qualifiées')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', 'qualifiee'))
                ->badge(Opportunite::where('statut', 'qualifiee')->count())
                ->badgeColor('primary'),

            'converties' => Tab::make('Converties')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', 'converti'))
                ->badge(Opportunite::where('statut', 'converti')->count())
                ->badgeColor('success'),

            'perdues' => Tab::make('Perdues')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', 'perdu'))
                ->badge(Opportunite::where('statut', 'perdu')->count())
                ->badgeColor('danger'),
        ];
    }
}
