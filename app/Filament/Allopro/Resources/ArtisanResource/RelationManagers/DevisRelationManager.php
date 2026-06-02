<?php
namespace App\Filament\Allopro\Resources\ArtisanResource\RelationManagers;

use App\Enums\StatutDevis;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DevisRelationManager extends RelationManager
{
    protected static string $relationship = 'devis';
    protected static ?string $title = 'Devis émis';
    protected static ?string $icon  = 'heroicon-o-document-text';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N° Devis')->weight('semibold'),
                Tables\Columns\TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutDevis ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutDevis ? $s->color() : 'gray'),
                Tables\Columns\TextColumn::make('total_ttc')->label('TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €'),
                Tables\Columns\TextColumn::make('ticket.reference')->label('Ticket'),
                Tables\Columns\TextColumn::make('date_validite')->label('Expire le')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('created_at')->label('Créé le')->date('d/m/Y'),
            ])
            ->actions([Tables\Actions\ViewAction::make()]);
    }
}
