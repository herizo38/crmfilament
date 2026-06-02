<?php
namespace App\Filament\Allopro\Resources\DevisResource\Pages;

use App\Enums\StatutDevis;
use App\Filament\Allopro\Resources\DevisResource;
use App\Models\Devis;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDevis extends ViewRecord
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('envoyer')
                ->label('Envoyer au client')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn() => $this->record->statut === StatutDevis::Brouillon)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->envoyer();
                    $this->refreshFormData(['statut']);
                    Notification::make()->title('Devis envoyé')->success()->send();
                }),

            Actions\Action::make('accepter')
                ->label('Marquer accepté')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => in_array($this->record->statut, [StatutDevis::Envoye, StatutDevis::Brouillon]))
                ->form([
                    Forms\Components\Select::make('mode_acceptation')
                        ->label('Mode d\'acceptation')
                        ->options([
                            'signature_electronique' => 'Signature électronique',
                            'appel'                  => 'Appel téléphonique',
                            'email'                  => 'Email',
                        ])
                        ->required()->native(false)->default('appel'),
                ])
                ->action(function (array $data) {
                    $bc = $this->record->accepter($data['mode_acceptation']);
                    $this->refreshFormData(['statut', 'date_acceptation_refus']);
                    Notification::make()
                        ->title('✅ Devis accepté — BC ' . $bc->numero . ' généré automatiquement')
                        ->success()->send();
                }),

            Actions\Action::make('refuser')
                ->label('Marquer refusé')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->record->statut === StatutDevis::Envoye)
                ->form([
                    Forms\Components\Textarea::make('motif')->label('Motif du refus')->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->refuser($data['motif'] ?? null);
                    $this->refreshFormData(['statut', 'date_acceptation_refus']);
                    Notification::make()->title('Devis refusé')->warning()->send();
                }),

            Actions\EditAction::make()
                ->visible(fn() => $this->record->statut === StatutDevis::Brouillon),
        ];
    }
}
