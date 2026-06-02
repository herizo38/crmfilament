<?php
namespace App\Filament\Allopro\Resources\BonDeCommandeResource\Pages;

use App\Enums\StatutBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;

use Filament\Resources\Pages\ViewRecord;
class ViewBonDeCommande extends ViewRecord
{
    protected static string $resource = BonDeCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirmer')
                ->label('Confirmer réception')
                ->icon('heroicon-o-check')
                ->color('info')
                ->visible(fn() => $this->record->statut === StatutBonDeCommande::EnAttente)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->confirmerParArtisan();
                    $this->refreshFormData(['statut', 'date_confirmation']);
                    Notification::make()->title('BC confirmé par l\'artisan')->success()->send();
                }),

            Actions\Action::make('planifier')
                ->label('Planifier intervention')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn() => $this->record->statut === StatutBonDeCommande::Confirme)
                ->form([
                    Forms\Components\DateTimePicker::make('date')
                        ->label('Date d\'intervention')->native(false)->required()->default(now()->addDay()),
                    Forms\Components\TextInput::make('duree')
                        ->label('Durée estimée (h)')->numeric()->step(0.5)->suffix('h'),
                ])
                ->action(function (array $data) {
                    $this->record->planifierIntervention(new \DateTime($data['date']), $data['duree'] ?? null);
                    $this->refreshFormData(['date_intervention_prevue', 'duree_estimee_heures']);
                    Notification::make()->title('Intervention planifiée')->success()->send();
                }),

            Actions\Action::make('demarrer')
                ->label('Démarrer')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn() => $this->record->statut === StatutBonDeCommande::Confirme)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->demarrerIntervention();
                    $this->refreshFormData(['statut']);
                    Notification::make()->title('Intervention démarrée')->success()->send();
                }),

            Actions\Action::make('realise')
                ->label('Marquer réalisé')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => in_array($this->record->statut, [StatutBonDeCommande::Confirme, StatutBonDeCommande::EnCours]))
                ->requiresConfirmation()
                ->modalHeading('Marquer l\'intervention comme réalisée ?')
                ->modalDescription('Une facture sera automatiquement générée et le ticket clôturé.')
                ->action(function () {
                    $facture = $this->record->marquerRealise();
                    $this->refreshFormData(['statut']);
                    Notification::make()
                        ->title('✅ Intervention réalisée — Facture ' . $facture->numero . ' générée')
                        ->success()->send();
                }),

            Actions\EditAction::make()->visible(fn() => $this->record->statut === StatutBonDeCommande::EnAttente),
        ];
    }
}
