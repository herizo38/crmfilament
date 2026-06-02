<?php

namespace App\Filament\Allopro\Resources\ReclamationP8Resource\Pages;

use App\Filament\Allopro\Resources\ReclamationP8Resource;
use App\Models\ReclamationP8;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListReclamationP8s extends ListRecords
{
    protected static string $resource = ReclamationP8Resource::class;

    protected function getHeaderActions(): array
    {
        return []; // Pas de création manuelle — uniquement via NPS ≤ 5
    }

    public function getTabs(): array
    {
        return [
            'actives' => Tab::make('Actives')
                ->badge(ReclamationP8::actives()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->actives()),

            'ouvertes'     => Tab::make('Ouvertes')
                ->badge(ReclamationP8::ouvertes()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->ouvertes()),

            'en_traitement' => Tab::make('En traitement')
                ->badge(ReclamationP8::enTraitement()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->enTraitement()),

            'a_valider'    => Tab::make('⏳ À valider superviseur')
                ->badge(ReclamationP8::aValider()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->aValider()),

            'en_retard'    => Tab::make('🔴 En retard SLA')
                ->badge(ReclamationP8::enRetard()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->enRetard()),

            'cloturees'    => Tab::make('Clôturées')
                ->badge(ReclamationP8::cloturees()->duMois()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->cloturees()),
        ];
    }
}
