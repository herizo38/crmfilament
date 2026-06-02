<?php
namespace App\Filament\Allopro\Resources\FactureResource\Pages;


use App\Filament\Allopro\Resources\FactureResource;
use App\Models\Facture;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;


class ListFactures extends ListRecords
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle facture')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'toutes'      => Tab::make('Toutes')->badge(Facture::count()),
            'en_attente'  => Tab::make('En attente')->badge(Facture::enAttente()->count())->badgeColor('info')->modifyQueryUsing(fn($q) => $q->enAttente()),
            'partielles'  => Tab::make('Partielles')->badge(Facture::partielles()->count())->badgeColor('warning')->modifyQueryUsing(fn($q) => $q->partielles()),
            'en_retard'   => Tab::make('⚠️ En retard')->badge(Facture::enRetard()->count())->badgeColor('danger')->modifyQueryUsing(fn($q) => $q->enRetard()),
            'a_relancer'  => Tab::make('À relancer')->badge(Facture::aRelancer()->count())->badgeColor('danger')->modifyQueryUsing(fn($q) => $q->aRelancer()),
            'litigieuses' => Tab::make('Litigieuses')->badge(Facture::litigieuses()->count())->badgeColor('danger')->modifyQueryUsing(fn($q) => $q->litigieuses()),
            'payees'      => Tab::make('Payées')->badge(Facture::payees()->count())->badgeColor('success')->modifyQueryUsing(fn($q) => $q->payees()),
        ];
    }
}
