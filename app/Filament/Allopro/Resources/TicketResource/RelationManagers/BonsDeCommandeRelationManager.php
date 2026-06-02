<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Enums\StatutBonDeCommande;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BonsDeCommandeRelationManager extends RelationManager
{
    protected static string $relationship = 'bonsDeCommande';
    protected static ?string $title = 'Bons de commande';
    protected static ?string $icon  = 'heroicon-o-clipboard-document-check';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N° BC')->weight('semibold'),
                Tables\Columns\TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutBonDeCommande ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutBonDeCommande ? $s->color() : 'gray'),
                Tables\Columns\TextColumn::make('date_intervention_prevue')->label('Intervention')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('montant_total_ttc')->label('Montant TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €'),
                Tables\Columns\IconColumn::make('acompte_encaisse')->label('Acompte')->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('realise')
                    ->label('Marquer réalisé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($r) => in_array($r->statut, [StatutBonDeCommande::Confirme, StatutBonDeCommande::EnCours]))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $facture = $record->marquerRealise();
                        Notification::make()->title('Facture ' . $facture->numero . ' générée')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
