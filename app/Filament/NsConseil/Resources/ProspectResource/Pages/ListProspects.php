<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Filament\NsConseil\Resources\ProspectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ Bouton campagne déplacé ici
            Actions\Action::make('campagne')
                ->label("Campagne d'appels")
                ->icon('heroicon-o-phone-arrow-up-right')
                ->url(PhoningWorkflow::getUrl())
                ->color('success'),

            Actions\CreateAction::make()->label('Nouveau prospect'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'tous' => Tab::make('Tous')
                ->badge(\App\Models\Prospect::count()),
        ];

        foreach (ProspectStatut::cases() as $statut) {
            $tabs[$statut->value] = Tab::make($statut->label())
                ->modifyQueryUsing(fn(Builder $q) => $q->where('statut', $statut))
                ->badge(\App\Models\Prospect::where('statut', $statut)->count())
                ->badgeColor($statut->color());
        }

        return $tabs;
    }
}