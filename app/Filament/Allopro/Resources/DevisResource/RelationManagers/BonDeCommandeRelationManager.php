<?php

namespace App\Filament\Allopro\Resources\DevisResource\RelationManagers;

use App\Enums\StatutBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource;
use App\Models\BonDeCommande;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BonDeCommandeRelationManager extends RelationManager
{
    protected static string $relationship = 'bonDeCommande';
    protected static ?string $title = 'Bon de commande';
    protected static ?string $icon  = 'heroicon-o-clipboard-document-check';

    public function form(Form $form): Form
    {
        return BonDeCommandeResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N° BC')->weight('semibold'),

                Tables\Columns\TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn($state) => $state instanceof StatutBonDeCommande ? $state->label() : $state)
                    ->color(fn($state) => $state instanceof StatutBonDeCommande ? $state->color() : 'gray'),


                Tables\Columns\TextColumn::make('date_intervention_prevue')
                    ->label('Intervention')->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('montant_total_ttc')
                    ->label('Montant TTC')
                    ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', ' ') . ' €'),

                Tables\Columns\IconColumn::make('acompte_encaisse')
                    ->label('Acompte')->boolean(),

                Tables\Columns\IconColumn::make('facture')
                    ->label('Facturé')
                    ->getStateUsing(fn($record) => $record->facture()->exists())
                    ->boolean()
                    ->trueColor('success')->falseColor('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirmer')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn(BonDeCommande $record) => $record->statut === StatutBonDeCommande::EnAttente)

                    ->action(function (BonDeCommande $record) {
                        $record->confirmerParArtisan();
                        Notification::make()->title('BC confirmé')->success()->send();
                    }),
                Tables\Actions\Action::make('realise')
                    ->label('Réalisé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(BonDeCommande $record) => in_array($record->statut, [StatutBonDeCommande::Confirme, StatutBonDeCommande::EnCours]))
                    ->requiresConfirmation()
                    ->action(function (BonDeCommande $record) {
                        $facture = $record->marquerRealise();
                        Notification::make()->title('Facture ' . $facture->numero . ' générée')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
