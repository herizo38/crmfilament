<?php
namespace App\Filament\Allopro\Resources\ReclamationP8Resource\Pages;

use App\Filament\Allopro\Resources\ReclamationP8Resource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;

use Filament\Resources\Pages\ViewRecord;


class ViewReclamationP8 extends ViewRecord
{
    protected static string $resource = ReclamationP8Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('prendre_en_charge')
                ->label('Prendre en charge')
                ->icon('heroicon-o-hand-raised')
                ->color('warning')
                ->visible(fn() => $this->record->estOuverte())
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Plan d\'action initial')->rows(3)->required(),
                ])
                ->action(function (array $data) {
                    $this->record->mettreEnTraitement($data['notes']);
                    $this->refreshFormData(['statut', 'notes_resolution']);
                    Notification::make()->title('Réclamation prise en charge')->warning()->send();
                }),

            Actions\Action::make('valider_superviseur')
                ->label('Valider (superviseur)')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->visible(fn() =>
                    $this->record->estEnTraitement() &&
                    auth()->user()?->hasAnyRole(['responsable_plateau', 'superviseur'])
                )
                ->form([
                    Forms\Components\Textarea::make('notes')->label('Observations')->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->validerParSuperviseur(auth()->user(), $data['notes'] ?? null);
                    $this->refreshFormData(['statut', 'validation_superviseur']);
                    Notification::make()->title('Validé par superviseur')->info()->send();
                }),

            Actions\Action::make('cloturer')
                ->label('Clôturer la réclamation')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->estValideeSuperviseur() || $this->record->estEnTraitement())
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Solution apportée')
                        ->rows(4)->required()
                        ->placeholder('Décrivez la résolution : actions menées, résultat pour le client…'),
                ])
                ->action(function (array $data) {
                    $this->record->cloturer($data['notes']);
                    $this->refreshFormData(['statut', 'date_resolution_effective', 'notes_resolution']);
                    Notification::make()->title('✅ Réclamation clôturée — Dossier archivé P7')->success()->send();
                }),

            Actions\EditAction::make()->visible(fn() => $this->record->estActive()),
        ];
    }
}
