<?php
namespace App\Filament\Allopro\Resources\BonDeCommandeResource\RelationManagers;

use App\Enums\ModePaiement;
use App\Enums\StatutPaiement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FactureRelationManager extends RelationManager
{
    protected static string $relationship = 'facture';
    protected static ?string $title = 'Facture';
    protected static ?string $icon  = 'heroicon-o-receipt-percent';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('numero')->label('N° Facture')->disabled()->dehydrated(false),
            Forms\Components\Select::make('statut_paiement')
                ->label('Statut paiement')
                ->options(collect(StatutPaiement::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                ->native(false)->required(),
            Forms\Components\DatePicker::make('date_echeance')->label('Échéance')->native(false)->required(),
            Forms\Components\Select::make('mode_paiement')
                ->label('Mode de paiement')
                ->options(collect(ModePaiement::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                ->native(false)->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N° Facture')->weight('semibold')->copyable(),
                Tables\Columns\TextColumn::make('statut_paiement')->label('Statut paiement')->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutPaiement ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutPaiement ? $s->color() : 'gray'),
                Tables\Columns\TextColumn::make('total_ttc')->label('TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')->weight('bold'),
                Tables\Columns\TextColumn::make('solde_restant_du')->label('Solde dû')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                    ->color(fn($s) => (float)$s > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('date_echeance')->label('Échéance')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('date_paiement_effectif')->label('Payé le')->date('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('payer')
                    ->label('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn($r) => !$r->est_payee)
                    ->form([
                        Forms\Components\TextInput::make('montant')
                            ->label('Montant reçu (€)')
                            ->numeric()->prefix('€')->required()
                            ->default(fn($r) => $r->solde_restant_du),
                        Forms\Components\Select::make('mode')
                            ->label('Mode')
                            ->options(collect(ModePaiement::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                            ->native(false)->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date de paiement')->native(false)->required()->default(today()),
                    ])
                    ->action(function ($record, array $data) {
                        $mode = ModePaiement::from($data['mode']);
                        $record->enregistrerPaiement($data['montant'], $mode, new \DateTime($data['date']));
                        Notification::make()->title('Paiement enregistré')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
