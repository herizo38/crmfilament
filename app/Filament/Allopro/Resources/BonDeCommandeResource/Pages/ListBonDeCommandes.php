<?php
namespace App\Filament\Allopro\Resources\BonDeCommandeResource\Pages;

use App\Filament\Allopro\Resources\BonDeCommandeResource;
use App\Models\BonDeCommande;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListBonDeCommandes extends ListRecords
{
    protected static string $resource = BonDeCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouveau BC')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous'         => Tab::make('Tous')->badge(BonDeCommande::count()),
            'en_attente'   => Tab::make('En attente')->badge(BonDeCommande::enAttente()->count())->badgeColor('warning')->modifyQueryUsing(fn($q) => $q->enAttente()),
            'confirmes'    => Tab::make('Confirmés')->badge(BonDeCommande::confirmes()->count())->badgeColor('info')->modifyQueryUsing(fn($q) => $q->confirmes()),
            'en_cours'     => Tab::make('En cours')->badge(BonDeCommande::enCours()->count())->badgeColor('primary')->modifyQueryUsing(fn($q) => $q->enCours()),
            'a_venir'      => Tab::make('Interventions à venir')->badge(BonDeCommande::interventionAVenir()->count())->badgeColor('info')->modifyQueryUsing(fn($q) => $q->interventionAVenir()),
            'acomptes'     => Tab::make('Acompte en attente')->badge(BonDeCommande::avecAcompteEnAttente()->count())->badgeColor('warning')->modifyQueryUsing(fn($q) => $q->avecAcompteEnAttente()),
            'sans_facture' => Tab::make('Réalisés sans facture')->badge(BonDeCommande::sansFacture()->count())->badgeColor('danger')->modifyQueryUsing(fn($q) => $q->sansFacture()),
            'realises'     => Tab::make('Réalisés')->badge(BonDeCommande::realises()->count())->badgeColor('success')->modifyQueryUsing(fn($q) => $q->realises()),
        ];
    }
}
