<?php
namespace App\Filament\Allopro\Resources\DevisResource\Pages;

use App\Enums\StatutDevis;
use App\Filament\Allopro\Resources\DevisResource;
use App\Models\Devis;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListDevis extends ListRecords
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau devis')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Devis::count()),

            'brouillons' => Tab::make('Brouillons')
                ->badge(Devis::where('statut', StatutDevis::Brouillon)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn($q) => $q->brouillons()),

            'en_attente' => Tab::make('En attente')
                ->badge(Devis::enAttente()->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn($q) => $q->enAttente()),

            'a_relancer' => Tab::make('À relancer')
                ->badge(Devis::aRelancer()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->aRelancer()),

            'acceptes' => Tab::make('Acceptés')
                ->badge(Devis::acceptes()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->acceptes()),

            'refuses' => Tab::make('Refusés')
                ->badge(Devis::refuses()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->refuses()),

            'expires' => Tab::make('Expirés')
                ->badge(Devis::expires()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->expires()),
        ];
    }
}
