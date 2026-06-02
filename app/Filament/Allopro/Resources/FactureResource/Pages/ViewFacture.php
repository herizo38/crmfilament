<?php
namespace App\Filament\Allopro\Resources\FactureResource\Pages;

use App\Enums\ModePaiement;
use App\Filament\Allopro\Resources\FactureResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;

use Filament\Resources\Pages\ViewRecord;

class ViewFacture extends ViewRecord
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('payer')
                ->label('Enregistrer paiement')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn() => !$this->record->est_payee && !$this->record->est_litigieux)
                ->form([
                    Forms\Components\TextInput::make('montant')->label('Montant reçu (€)')
                        ->numeric()->prefix('€')->required()
                        ->default(fn() => $this->record->solde_restant_du),
                    Forms\Components\Select::make('mode')->label('Mode de paiement')
                        ->options(collect(ModePaiement::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                        ->native(false)->required(),
                    Forms\Components\DatePicker::make('date')->label('Date')
                        ->native(false)->required()->default(today()),
                ])
                ->action(function (array $data) {
                    $this->record->enregistrerPaiement(
                        $data['montant'],
                        ModePaiement::from($data['mode']),
                        new \DateTime($data['date'])
                    );
                    $this->refreshFormData(['statut_paiement', 'solde_restant_du', 'date_paiement_effectif']);
                    Notification::make()->title('Paiement enregistré')->success()->send();
                }),

            Actions\Action::make('litige')
                ->label('Déclarer litige')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->visible(fn() => $this->record->est_en_retard && !$this->record->est_litigieux)
                ->form([
                    Forms\Components\Textarea::make('motif')->label('Motif du litige')->rows(3)->required(),
                ])
                ->action(function (array $data) {
                    $this->record->marquerLitigieux($data['motif']);
                    $this->refreshFormData(['statut_paiement']);
                    Notification::make()->title('Facture marquée litigieuse')->warning()->send();
                }),

            Actions\Action::make('calculer_penalites')
                ->label('Calculer pénalités')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn() => $this->record->est_en_retard && !$this->record->est_payee)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->calculerPenalites();
                    $this->refreshFormData(['penalites_retard', 'statut_paiement']);
                    Notification::make()->title('Pénalités calculées')->warning()->send();
                }),
        ];
    }
}
