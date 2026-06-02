<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Enums\StatutDevis;
use App\Filament\Allopro\Resources\DevisResource;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DevisRelationManager extends RelationManager
{
    protected static string $relationship = 'devis';
    protected static ?string $title = 'Devis';
    protected static ?string $icon  = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return DevisResource::form($form);
    }

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
                Tables\Columns\TextColumn::make('date_validite')->label('Expire le')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer un devis')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['ticket_id'] = $this->getOwnerRecord()->id;
                        $data['artisan_id'] = $data['artisan_id'] ?? $this->getOwnerRecord()->artisan_id;
                        $data['contact_particulier_id'] = $data['contact_particulier_id']
                            ?? $this->getOwnerRecord()->contact_particulier_id;
                        $data['numero'] = \App\Models\Devis::genererNumero();
                        return $data;
                    })
                    ->visible(fn() => auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau'])),
            ])
            ->actions([
                Tables\Actions\Action::make('accepter')
                    ->label('Accepter')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($r) => in_array($r->statut, [StatutDevis::Envoye, StatutDevis::Brouillon]))
                    ->action(function ($record) {
                        $bc = $record->accepter('appel');
                        Notification::make()->title('Devis accepté — BC ' . $bc->numero)->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
