<?php
namespace App\Filament\Allopro\Resources\ArtisanResource\RelationManagers;

use App\Enums\StatutPaiement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FacturesRelationManager extends RelationManager
{
    protected static string $relationship = 'factures';
    protected static ?string $title = 'Factures';
    protected static ?string $icon  = 'heroicon-o-receipt-percent';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N° Facture')->weight('semibold')->copyable(),
                Tables\Columns\TextColumn::make('statut_paiement')->label('Paiement')->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutPaiement ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutPaiement ? $s->color() : 'gray'),
                Tables\Columns\TextColumn::make('total_ttc')->label('TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €'),
                Tables\Columns\TextColumn::make('solde_restant_du')->label('Solde dû')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                    ->color(fn($s) => (float)$s > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('date_echeance')->label('Échéance')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('penalites_retard')->label('Pénalités')
                    ->formatStateUsing(fn($s) => (float)$s > 0 ? number_format((float)$s, 2, ',', ' ') . ' €' : '—')
                    ->color('danger'),
            ])
            ->actions([Tables\Actions\ViewAction::make()]);
    }
}
